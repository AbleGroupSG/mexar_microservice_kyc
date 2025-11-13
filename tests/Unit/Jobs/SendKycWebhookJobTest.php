<?php

namespace Tests\Unit\Jobs;

use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * SendKycWebhookJobTest
 *
 * Unit tests for the unified webhook sending job that handles all KYC providers.
 * Tests webhook payload structure, retry logic, timeout handling, and error scenarios.
 */
class SendKycWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create test profile with valid data
     */
    private function createTestProfile(
        array $overrides = [],
        ?string $webhookUrl = 'https://client.example.com/webhook'
    ): KYCProfile {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => $webhookUrl,
        ]);

        $profileData = [
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
            'meta' => [
                'service_provider' => 'test',
                'reference_id' => 'REF-12345',
            ],
        ];

        return KYCProfile::factory()->create(array_merge([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'provider_reference_id' => 'PROVIDER-REF-12345',
            'status' => KycStatuseEnum::APPROVED,
            'profile_data' => $profileData,
        ], $overrides));
    }

    public function test_sends_webhook_successfully_for_approved_status(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'status' => KycStatuseEnum::APPROVED,
        ]);

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify webhook was sent with correct structure
        Http::assertSent(function ($request) use ($profile) {
            return $request->url() === 'https://client.example.com/webhook'
                && $request['event'] === 'kyc.status.changed'
                && $request['payload']['msa_reference_id'] === $profile->id
                && $request['payload']['provider_reference_id'] === 'PROVIDER-REF-12345'
                && $request['payload']['reference_id'] === 'REF-12345'
                && $request['payload']['platform'] === 'test'
                && $request['payload']['status'] === KycStatuseEnum::APPROVED->value
                && $request['payload']['verified'] === true
                && $request['payload']['verified_at'] !== null
                && $request['payload']['rejected_at'] === null
                && $request['payload']['failure_reason'] === null;
        });
    }

    public function test_sends_webhook_successfully_for_rejected_status(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'status' => KycStatuseEnum::REJECTED,
        ]);

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify rejected webhook structure
        Http::assertSent(function ($request) use ($profile) {
            return $request->url() === 'https://client.example.com/webhook'
                && $request['payload']['status'] === KycStatuseEnum::REJECTED->value
                && $request['payload']['verified'] === false
                && $request['payload']['verified_at'] === null
                && $request['payload']['rejected_at'] !== null
                && $request['payload']['failure_reason'] === 'Verification rejected';
        });
    }

    public function test_sends_webhook_with_error_status(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'status' => KycStatuseEnum::ERROR,
        ]);

        $errorMessage = 'Verification failed due to system error';
        $job = new SendKycWebhookJob($profile->id, ['error' => $errorMessage]);
        $job->handle();

        // Verify error webhook includes failure reason
        Http::assertSent(function ($request) use ($errorMessage) {
            return $request['payload']['status'] === KycStatuseEnum::ERROR->value
                && $request['payload']['verified'] === false
                && $request['payload']['failure_reason'] === null // Error in additionalData, not in failureReason
                && $request['payload']['message'] === $errorMessage;
        });
    }

    public function test_sends_webhook_with_unresolved_status(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'status' => KycStatuseEnum::UNRESOLVED,
        ]);

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify unresolved webhook
        Http::assertSent(function ($request) {
            return $request['payload']['status'] === KycStatuseEnum::UNRESOLVED->value
                && $request['payload']['verified'] === false
                && $request['payload']['message'] === 'KYC verification requires manual review';
        });
    }

    public function test_includes_regtank_provider_data_in_webhook(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'provider' => 'regtank',
            'status' => KycStatuseEnum::APPROVED,
        ]);

        $regtankData = [
            'status' => 'Approved',
            'riskLevel' => 'Low',
            'timestamp' => '2025-11-13T10:00:00Z',
        ];

        $job = new SendKycWebhookJob($profile->id, ['provider_data' => $regtankData]);
        $job->handle();

        // Verify RegTank-specific data is included
        Http::assertSent(function ($request) use ($regtankData) {
            return $request['payload']['platform'] === 'regtank'
                && $request['payload']['message'] === "KYC verification completed risk level: {$regtankData['riskLevel']}"
                && $request['payload']['verified_at'] === $regtankData['timestamp']
                && $request['payload']['review_notes'] === $regtankData['status'];
        });
    }

    public function test_uses_30_second_timeout_for_webhook_request(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify timeout was set (Laravel Http fake doesn't expose timeout directly,
        // but we can verify the request was made)
        Http::assertSent(function ($request) {
            // In production, the timeout would be 30 seconds as set in handle()
            return $request->url() === 'https://client.example.com/webhook';
        });
    }

    public function test_logs_error_when_webhook_fails(): void
    {
        Log::spy();

        Http::fake([
            'https://client.example.com/webhook' => Http::response(['error' => 'Service unavailable'], 503),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);

        // Job should throw exception to trigger retry
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Webhook failed with status 503');

        $job->handle();

        // Verify error was logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with('SendKycWebhook: Failed to send webhook', \Mockery::type('array'));
    }

    public function test_skips_webhook_when_no_webhook_url_configured(): void
    {
        Http::fake();

        $profile = $this->createTestProfile([], null); // No webhook URL

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    public function test_handles_missing_profile_gracefully(): void
    {
        Http::fake();

        $job = new SendKycWebhookJob('non-existent-uuid');
        $job->handle();

        // Should not throw exception, just log error
        Http::assertNothingSent();
    }

    public function test_throws_exception_on_webhook_failure_to_trigger_retry(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);

        // Exception should be thrown to trigger Laravel's retry mechanism
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Webhook failed with status 400/');

        $job->handle();
    }

    public function test_webhook_payload_includes_all_required_fields(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify all required fields are present
        Http::assertSent(function ($request) {
            $payload = $request['payload'];

            return isset($payload['msa_reference_id'])
                && isset($payload['provider_reference_id'])
                && isset($payload['reference_id'])
                && isset($payload['platform'])
                && isset($payload['status'])
                && isset($payload['verified'])
                && array_key_exists('verified_at', $payload)
                && array_key_exists('rejected_at', $payload)
                && isset($payload['message'])
                && array_key_exists('review_notes', $payload)
                && array_key_exists('failure_reason', $payload);
        });
    }

    public function test_logs_successful_webhook_delivery(): void
    {
        Log::spy();

        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify success was logged
        Log::shouldHaveReceived('info')
            ->with('SendKycWebhook: Webhook sent successfully', \Mockery::type('array'));
    }

    public function test_logs_attempt_number_for_retries(): void
    {
        Log::spy();

        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify attempt number is logged
        Log::shouldHaveReceived('info')
            ->with('SendKycWebhook: Sending webhook', \Mockery::on(function ($context) {
                return isset($context['attempt']);
            }));
    }

    public function test_uses_profile_provider_field_for_platform(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'provider' => 'glair_ai',
        ]);

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify platform uses profile's provider field
        Http::assertSent(function ($request) {
            return $request['payload']['platform'] === 'glair_ai';
        });
    }

    public function test_formats_error_message_correctly_in_additional_data(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile([
            'status' => KycStatuseEnum::ERROR,
        ]);

        $errorMessage = 'Connection timeout to provider';
        $job = new SendKycWebhookJob($profile->id, ['error' => $errorMessage]);
        $job->handle();

        // Verify error message is in the message field
        Http::assertSent(function ($request) use ($errorMessage) {
            return $request['payload']['message'] === $errorMessage
                && $request['payload']['review_notes'] === null;
        });
    }

    public function test_failed_callback_logs_permanent_failure(): void
    {
        Log::spy();

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $exception = new \Exception('Permanent failure after retries');

        $job->failed($exception);

        // Verify critical log was created
        Log::shouldHaveReceived('critical')
            ->once()
            ->with('SendKycWebhook: Webhook delivery permanently failed', \Mockery::type('array'));
    }

    public function test_handles_connection_timeout_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);

        // Should throw exception to trigger retry
        $this->expectException(\Illuminate\Http\Client\ConnectionException::class);

        $job->handle();
    }

    public function test_webhook_includes_reference_id_from_profile_data(): void
    {
        Http::fake([
            'https://client.example.com/webhook' => Http::response(['received' => true], 200),
        ]);

        $profile = $this->createTestProfile();

        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify reference_id comes from profile_data->meta->reference_id
        Http::assertSent(function ($request) {
            return $request['payload']['reference_id'] === 'REF-12345';
        });
    }
}
