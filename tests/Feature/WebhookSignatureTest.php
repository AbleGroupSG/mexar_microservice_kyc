<?php

namespace Tests\Feature;

use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * WebhookSignatureTest
 *
 * Tests webhook signature generation and header inclusion.
 *
 * ## What We Test:
 *
 * ### 1. Signature Generation
 * - Signature header present when signature_key is configured
 * - No signature header when signature_key is null
 *
 * ### 2. Signature Validity
 * - Signature matches expected HMAC-SHA256 format
 * - Timestamp header is present and valid
 */
class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function createProfileWithWebhook(array $apiKeyAttributes = []): KYCProfile
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create(array_merge([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
        ], $apiKeyAttributes));

        $profileId = Str::uuid()->toString();

        return KYCProfile::create([
            'id' => $profileId,
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::APPROVED,
            'profile_data' => [
                'uuid' => $profileId,
                'personal_info' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
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
                    'address_line' => '123 Main St',
                ],
                'meta' => [
                    'service_provider' => 'test',
                    'reference_id' => 'TEST-001',
                ],
            ],
        ]);
    }

    public function test_webhook_includes_signature_headers_when_signature_key_configured(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $signatureKey = Str::random(32);
        $profile = $this->createProfileWithWebhook([
            'signature_key' => $signatureKey,
        ]);

        // Dispatch the job
        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Assert HTTP request was made with signature headers
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Webhook-Signature')
                && $request->hasHeader('X-Webhook-Timestamp');
        });
    }

    public function test_webhook_excludes_signature_headers_when_signature_key_null(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $profile = $this->createProfileWithWebhook([
            'signature_key' => null,
        ]);

        // Dispatch the job
        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Assert HTTP request was made without signature headers
        Http::assertSent(function ($request) {
            return ! $request->hasHeader('X-Webhook-Signature')
                && ! $request->hasHeader('X-Webhook-Timestamp');
        });
    }

    public function test_webhook_signature_is_valid_hmac_sha256(): void
    {
        $capturedRequest = null;

        Http::fake(function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;

            return Http::response(['status' => 'ok'], 200);
        });

        $signatureKey = 'test-secret-key-for-signature';
        $profile = $this->createProfileWithWebhook([
            'signature_key' => $signatureKey,
        ]);

        // Dispatch the job
        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        // Verify signature is valid
        $this->assertNotNull($capturedRequest, 'HTTP request was not captured');

        $timestamp = $capturedRequest->header('X-Webhook-Timestamp')[0];
        $signature = $capturedRequest->header('X-Webhook-Signature')[0];
        $body = $capturedRequest->body();

        // Recalculate expected signature
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $body, $signatureKey);

        $this->assertEquals(
            $expectedSignature,
            $signature,
            'Webhook signature does not match expected HMAC-SHA256'
        );
    }

    public function test_webhook_timestamp_is_recent(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $profile = $this->createProfileWithWebhook([
            'signature_key' => Str::random(32),
        ]);

        $beforeTime = time();

        // Dispatch the job
        $job = new SendKycWebhookJob($profile->id);
        $job->handle();

        $afterTime = time();

        // Assert timestamp is within expected range
        Http::assertSent(function ($request) use ($beforeTime, $afterTime) {
            $timestamp = (int) $request->header('X-Webhook-Timestamp')[0];

            return $timestamp >= $beforeTime && $timestamp <= $afterTime;
        });
    }
}
