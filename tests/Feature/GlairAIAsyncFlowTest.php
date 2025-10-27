<?php

namespace Tests\Feature;

use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Jobs\GlairAIVerificationJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GlairAIAsyncFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get valid test data for GlairAI screening request
     */
    private function getValidScreeningData(): array
    {
        return [
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ];
    }

    public function test_glair_ai_screen_returns_only_identity_reference(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/screen', $this->getValidScreeningData());

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

    public function test_glair_ai_screen_creates_profile_with_pending_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/screen', $this->getValidScreeningData());

        $data = $response->json('data');

        // Verify profile was created with PENDING status
        $this->assertDatabaseHas('kyc_profiles', [
            'id' => $data['identity'],
            'provider' => 'glair',
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::PENDING,
        ]);
    }

    public function test_glair_ai_screen_dispatches_verification_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(['user_id' => $user->id]);

        $this->withHeaders([
            'X-API-KEY' => $apiKey->api_key,
        ])->postJson('/api/screen', $this->getValidScreeningData());

        // Verify the new async job is dispatched
        Queue::assertPushed(GlairAIVerificationJob::class);
    }

    public function test_glair_ai_verification_job_updates_profile_to_approved(): void
    {
        Http::fake([
            '*/identity/v1/verification' => Http::response([
                'verification_status' => true,
                'reason' => 'Verified',
            ], 200),
            '*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'glair_ai',
            'status' => KycStatuseEnum::PENDING,
        ]);

        $userDataDTO = \App\DTO\UserDataDTO::from([
            'uuid' => $profile->id,
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ]);

        $data = [
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'date_of_birth' => '01-01-1990',
        ];

        $job = new GlairAIVerificationJob($profile->id, $userDataDTO, $data);
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertNotNull($profile->provider_response_data);
    }

    public function test_glair_ai_verification_job_updates_profile_to_rejected(): void
    {
        Http::fake([
            '*/identity/v1/verification' => Http::response([
                'verification_status' => false,
                'reason' => 'Not verified',
            ], 200),
            '*' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'glair_ai',
            'status' => KycStatuseEnum::PENDING,
        ]);

        $userDataDTO = \App\DTO\UserDataDTO::from([
            'uuid' => $profile->id,
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ]);

        $data = [
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'date_of_birth' => '01-01-1990',
        ];

        $job = new GlairAIVerificationJob($profile->id, $userDataDTO, $data);
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::REJECTED, $profile->status);
    }

    public function test_glair_ai_verification_job_sends_webhook_to_client_webhook_url(): void
    {
        Http::fake([
            '*/identity/v1/verification' => Http::response([
                'verification_status' => true,
                'reason' => 'Verified',
            ], 200),
            '*/webhook' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'glair_ai',
            'status' => KycStatuseEnum::PENDING,
        ]);

        $userDataDTO = \App\DTO\UserDataDTO::from([
            'uuid' => $profile->id,
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ]);

        $data = [
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'date_of_birth' => '01-01-1990',
        ];

        $job = new GlairAIVerificationJob($profile->id, $userDataDTO, $data);
        $job->handle();

        // Verify webhook was sent to client's webhook URL
        Http::assertSent(function ($request) {
            return $request->url() === 'https://client.example.com/webhook'
                && $request['event'] === 'kyc.status.changed'
                && $request['payload']['platform'] === KycServiceTypeEnum::GLAIR_AI
                && $request['payload']['status'] === KycStatuseEnum::APPROVED;
        });
    }

    public function test_glair_ai_verification_job_handles_errors_gracefully(): void
    {
        Http::fake([
            '*/identity/v1/verification' => Http::response([
                'error' => 'Invalid request',
            ], 400),
            '*/webhook' => Http::response(['received' => true], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://client.example.com/webhook',
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'glair_ai',
            'status' => KycStatuseEnum::PENDING,
        ]);

        $userDataDTO = \App\DTO\UserDataDTO::from([
            'uuid' => $profile->id,
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ]);

        $data = [
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'date_of_birth' => '01-01-1990',
        ];

        $job = new GlairAIVerificationJob($profile->id, $userDataDTO, $data);
        $job->handle();

        $profile->refresh();

        // Profile should be marked as ERROR
        $this->assertEquals(KycStatuseEnum::ERROR, $profile->status);

        // Webhook should still be sent with error details
        Http::assertSent(function ($request) {
            return $request->url() === 'https://client.example.com/webhook'
                && $request['payload']['status'] === KycStatuseEnum::ERROR
                && !empty($request['payload']['failure_reason']);
        });
    }

    public function test_glair_ai_verification_job_skips_webhook_when_no_webhook_url(): void
    {
        Http::fake([
            '*/identity/v1/verification' => Http::response([
                'verification_status' => true,
                'reason' => 'Verified',
            ], 200),
        ]);

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => null, // No webhook URL
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'glair_ai',
            'status' => KycStatuseEnum::PENDING,
        ]);

        $userDataDTO = \App\DTO\UserDataDTO::from([
            'uuid' => $profile->id,
            'personal_info' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'nationality' => 'ID',
            ],
            'identification' => [
                'id_type' => 'national_id',
                'id_number' => '1234567890123456',
                'issuing_country' => 'ID',
            ],
            'address' => [
                'city' => 'Jakarta',
                'country' => 'ID',
                'address_line' => 'Jl. Test No. 123',
            ],
            'meta' => [
                'service_provider' => 'glair',
                'reference_id' => 'REF123',
            ],
        ]);

        $data = [
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'date_of_birth' => '01-01-1990',
        ];

        $job = new GlairAIVerificationJob($profile->id, $userDataDTO, $data);
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);

        // No webhook should be sent (only the verification API call)
        Http::assertSentCount(1); // Only the verification call, no webhook
        Http::assertSent(fn($request) => str_contains($request->url(), '/identity/v1/verification'));
    }
}
