<?php

namespace Tests\Feature;

use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Jobs\SendKycWebhookJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Models\WebhookLog;
use App\Services\EFormAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * WebhookIdempotencyTest
 *
 * Tests that duplicate webhooks from RegTank are detected and skipped
 * across all 4 webhook endpoints: kyc, djkyc, djkyb, liveness.
 */
class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function buildKycWebhookPayload(string $requestId = 'REQ-123'): array
    {
        return [
            'requestId' => $requestId,
            'referenceId' => 'REF-001',
            'riskScore' => '50',
            'riskLevel' => 'MEDIUM',
            'status' => 'Approved',
            'messageStatus' => 'completed',
            'possibleMatchCount' => 0,
            'blacklistedMatchCount' => 0,
            'assignee' => 'admin',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function createProfileForWebhook(string $providerReferenceId): KYCProfile
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ]);

        return KYCProfile::factory()->pending()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider_reference_id' => $providerReferenceId,
        ]);
    }

    // ---- KYC endpoint idempotency ----

    public function test_kyc_webhook_processes_first_request_normally(): void
    {
        Queue::fake();

        $profile = $this->createProfileForWebhook('REQ-123');
        $payload = $this->buildKycWebhookPayload('REQ-123');

        $response = $this->postJson('/kyc', $payload);

        $response->assertOk()->assertJson(['status' => true]);

        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:kyc:REQ-123',
        ]);

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);

        Queue::assertPushed(SendKycWebhookJob::class, 1);
    }

    public function test_kyc_webhook_skips_duplicate_request(): void
    {
        Queue::fake();

        $profile = $this->createProfileForWebhook('REQ-123');
        $payload = $this->buildKycWebhookPayload('REQ-123');

        // First request - should process
        $this->postJson('/kyc', $payload)->assertOk();

        // Second request - should skip
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Duplicate webhook detected');
            });

        $response = $this->postJson('/kyc', $payload);

        $response->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseCount('webhook_logs', 1);
        Queue::assertPushed(SendKycWebhookJob::class, 1);
    }

    // ---- DJKYC endpoint idempotency ----

    public function test_djkyc_webhook_processes_first_request_normally(): void
    {
        Queue::fake();

        $profile = $this->createProfileForWebhook('REQ-456');
        $payload = $this->buildKycWebhookPayload('REQ-456');

        $response = $this->postJson('/djkyc', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:djkyc:REQ-456',
        ]);
        Queue::assertPushed(SendKycWebhookJob::class, 1);
    }

    public function test_djkyc_webhook_skips_duplicate_request(): void
    {
        Queue::fake();

        $profile = $this->createProfileForWebhook('REQ-456');
        $payload = $this->buildKycWebhookPayload('REQ-456');

        $this->postJson('/djkyc', $payload)->assertOk();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Duplicate webhook detected');
            });

        $response = $this->postJson('/djkyc', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
        Queue::assertPushed(SendKycWebhookJob::class, 1);
    }

    // ---- DJKYB endpoint idempotency ----

    public function test_djkyb_webhook_processes_first_request_normally(): void
    {
        $mock = $this->mock(EFormAppService::class);
        $mock->shouldReceive('sendDjkyb')->once();

        $payload = ['requestId' => 'REQ-789', 'data' => 'test'];

        $response = $this->postJson('/djkyb', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:djkyb:REQ-789',
        ]);
    }

    public function test_djkyb_webhook_skips_duplicate_request(): void
    {
        $mock = $this->mock(EFormAppService::class);
        $mock->shouldReceive('sendDjkyb')->once();

        $payload = ['requestId' => 'REQ-789', 'data' => 'test'];

        $this->postJson('/djkyb', $payload)->assertOk();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Duplicate webhook detected');
            });

        $response = $this->postJson('/djkyb', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
    }

    // ---- Liveness endpoint idempotency ----

    public function test_liveness_webhook_processes_first_request_normally(): void
    {
        $mock = $this->mock(EFormAppService::class);
        $mock->shouldReceive('sendLiveness')->once();

        $payload = ['requestId' => 'REQ-101', 'data' => 'test'];

        $response = $this->postJson('/liveness', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:liveness:REQ-101',
        ]);
    }

    public function test_liveness_webhook_skips_duplicate_request(): void
    {
        $mock = $this->mock(EFormAppService::class);
        $mock->shouldReceive('sendLiveness')->once();

        $payload = ['requestId' => 'REQ-101', 'data' => 'test'];

        $this->postJson('/liveness', $payload)->assertOk();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Duplicate webhook detected');
            });

        $response = $this->postJson('/liveness', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('webhook_logs', 1);
    }

    // ---- Cross-type independence ----

    public function test_same_request_id_different_types_are_independent(): void
    {
        Queue::fake();

        $profile = $this->createProfileForWebhook('REQ-SHARED');
        $payload = $this->buildKycWebhookPayload('REQ-SHARED');

        $this->postJson('/kyc', $payload)->assertOk();
        $this->postJson('/djkyc', $payload)->assertOk();

        $this->assertDatabaseCount('webhook_logs', 2);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:kyc:REQ-SHARED',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'idempotency_key' => 'regtank:djkyc:REQ-SHARED',
        ]);

        Queue::assertPushed(SendKycWebhookJob::class, 2);
    }

    public function test_different_request_ids_are_not_treated_as_duplicates(): void
    {
        Queue::fake();

        $profile1 = $this->createProfileForWebhook('REQ-AAA');
        $profile2 = $this->createProfileForWebhook('REQ-BBB');

        $this->postJson('/kyc', $this->buildKycWebhookPayload('REQ-AAA'))->assertOk();
        $this->postJson('/kyc', $this->buildKycWebhookPayload('REQ-BBB'))->assertOk();

        $this->assertDatabaseCount('webhook_logs', 2);
        Queue::assertPushed(SendKycWebhookJob::class, 2);
    }

    // ---- Idempotency key format ----

    public function test_idempotency_key_format_is_correct(): void
    {
        $key = WebhookLog::buildIdempotencyKey('regtank', WebhookTypeEnum::KYC, 'REQ-123');

        $this->assertEquals('regtank:kyc:REQ-123', $key);
    }

    public function test_idempotency_key_includes_type_differentiation(): void
    {
        $kycKey = WebhookLog::buildIdempotencyKey('regtank', WebhookTypeEnum::KYC, 'REQ-123');
        $djkycKey = WebhookLog::buildIdempotencyKey('regtank', WebhookTypeEnum::DJKYC, 'REQ-123');

        $this->assertNotEquals($kycKey, $djkycKey);
        $this->assertEquals('regtank:kyc:REQ-123', $kycKey);
        $this->assertEquals('regtank:djkyc:REQ-123', $djkycKey);
    }
}
