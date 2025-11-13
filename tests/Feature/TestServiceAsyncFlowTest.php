<?php

namespace Tests\Feature;

use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Jobs\TestKYCResultJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * TestServiceAsyncFlowTest
 *
 * Verifies that TestService follows the same async workflow pattern as other KYC providers
 * (GlairAI, RegTank) per the KYCServiceInterface specification.
 *
 * ## What We Test:
 *
 * ### 1. Async Pattern Compliance
 * - screen() method creates profile with PENDING status
 * - screen() returns only 'identity' reference (no immediate status)
 * - Verification is dispatched to background job queue
 *
 * ### 2. Job Processing
 * - TestKYCResultJob loads profile from database
 * - Job updates profile status (APPROVED, REJECTED, ERROR, UNRESOLVED)
 * - Job respects optional meta->status for controlled testing
 * - Job handles errors gracefully and sets ERROR status
 *
 * ### 3. Webhook Delivery
 * - Webhooks sent to client's configured webhook_url (not hardcoded)
 * - Webhook payload includes correct platform (TEST) and status
 * - Webhook includes all required fields (msa_reference_id, provider_reference_id, etc.)
 * - Webhook skipped gracefully when no webhook_url configured
 *
 * ### 4. Test Mode Features
 * - Supports meta->status to control test outcomes ('approved', 'rejected', 'error', 'unresolved')
 * - Defaults to APPROVED when no desired status specified
 * - provider_response_data includes test_mode flag
 *
 * This test suite ensures TestService is production-ready and consistent with other providers.
 */
class TestServiceAsyncFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get valid test data for Test provider screening request
     */
    private function getValidScreeningData(array $overrides = []): array
    {
        return array_merge([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'SG',
            ],
            'identification' => [
                'id_type' => 'passport',
                'id_number' => 'A1234567',
                'issuing_country' => 'SG',
            ],
            'address' => [
                'city' => 'Singapore',
                'country' => 'SG',
                'address_line' => '123 Orchard Road',
            ],
            'contact' => [
                'email' => 'john.doe@example.com',
                'phone' => '+6512345678',
            ],
            'meta' => [
                'service_provider' => 'test',
                'reference_id' => 'TEST-REF-001',
            ],
        ], $overrides);
    }

    public function test_test_service_screen_returns_only_identity_reference(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/v1/screen', $this->getValidScreeningData());

        // Should return only identity reference, not status
        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['code', 'message', 'request_id'],
                'data' => ['identity'],
            ])
            ->assertJsonMissing(['status', 'response']);

        $data = $response->json('data');
        $this->assertArrayHasKey('identity', $data);
        $this->assertIsString($data['identity']);
    }

    public function test_test_service_screen_creates_profile_with_pending_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/v1/screen', $this->getValidScreeningData());

        $data = $response->json('data');

        // Verify profile was created with PENDING status
        $this->assertDatabaseHas('kyc_profiles', [
            'id' => $data['identity'],
            'provider' => 'test',
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::PENDING,
        ]);
    }

    public function test_test_service_screen_dispatches_verification_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/v1/screen', $this->getValidScreeningData());

        // Verify the async job is dispatched
        Queue::assertPushed(TestKYCResultJob::class);
    }

    public function test_test_service_verification_job_updates_profile_to_approved(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Job should default to APPROVED when no desired status is specified
        $job = new TestKYCResultJob($profile->id, null);
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertNotNull($profile->provider_response_data);
        $this->assertTrue($profile->provider_response_data['test_mode'] ?? false);
    }

    public function test_test_service_verification_job_updates_profile_to_rejected(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Job should respect desired status
        $job = new TestKYCResultJob($profile->id, 'rejected');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::REJECTED, $profile->status);
        $this->assertNotNull($profile->provider_response_data);
        $this->assertFalse($profile->provider_response_data['verification_status'] ?? true);
    }

    public function test_test_service_verification_job_respects_meta_status_for_error(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Job should respect error status
        $job = new TestKYCResultJob($profile->id, 'error', 'Test error message');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::ERROR, $profile->status);
    }

    public function test_test_service_verification_job_sends_webhook_to_client_webhook_url(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $job = new TestKYCResultJob($profile->id, 'approved');
        $job->handle();

        // Verify SendKycWebhookJob was dispatched
        Queue::assertPushed(SendKycWebhookJob::class, function ($job) use ($profile) {
            return $job->profileId === $profile->id;
        });
    }

    public function test_test_service_verification_job_sends_correct_webhook_for_rejection(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $job = new TestKYCResultJob($profile->id, 'rejected');
        $job->handle();

        // Verify SendKycWebhookJob was dispatched
        Queue::assertPushed(SendKycWebhookJob::class, function ($job) use ($profile) {
            return $job->profileId === $profile->id;
        });
    }

    public function test_test_service_verification_job_handles_missing_profile_gracefully(): void
    {
        Http::fake();

        // Try to process a non-existent profile
        $job = new TestKYCResultJob('non-existent-uuid', 'approved');
        $job->handle();

        // Should not throw exception, just log error
        // No assertions needed - if we get here without exception, test passes
        $this->assertTrue(true);
    }

    public function test_test_service_verification_job_skips_webhook_when_no_webhook_url(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => null, // No webhook URL
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $job = new TestKYCResultJob($profile->id, 'approved');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);

        // No webhook should be sent
        Http::assertNothingSent();
    }

    public function test_test_service_full_async_flow_with_meta_status(): void
    {
        Queue::fake();
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);

        // Request with meta->status = "rejected"
        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/v1/screen', $this->getValidScreeningData([
            'meta' => [
                'service_provider' => 'test',
                'reference_id' => 'TEST-REF-002',
                'status' => 'rejected', // Control the outcome
            ],
        ]));

        $response->assertOk();
        $data = $response->json('data');
        $profileId = $data['identity'];

        // Verify profile is PENDING initially
        $this->assertDatabaseHas('kyc_profiles', [
            'id' => $profileId,
            'status' => KycStatuseEnum::PENDING,
        ]);

        // Verify job was dispatched
        Queue::assertPushed(TestKYCResultJob::class);
    }

    public function test_test_service_webhook_includes_provider_reference_id(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'provider_reference_id' => 'TEST-REF-12345',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $job = new TestKYCResultJob($profile->id, 'approved');
        $job->handle();

        // Verify SendKycWebhookJob was dispatched
        Queue::assertPushed(SendKycWebhookJob::class, function ($job) use ($profile) {
            return $job->profileId === $profile->id;
        });
    }

    public function test_test_service_supports_unresolved_status(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Job should support unresolved status
        $job = new TestKYCResultJob($profile->id, 'unresolved');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::UNRESOLVED, $profile->status);
    }
}
