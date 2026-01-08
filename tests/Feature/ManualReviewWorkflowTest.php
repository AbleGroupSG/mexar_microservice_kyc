<?php

namespace Tests\Feature;

use App\Enums\KycStatuseEnum;
use App\Jobs\SendKycWebhookJob;
use App\Jobs\TestKYCResultJob;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\KYC\KycWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ManualReviewWorkflowTest
 *
 * Tests the double verification workflow where KYC results from providers
 * can require manual admin review before the final webhook is sent to clients.
 *
 * ## What We Test:
 *
 * ### 1. Workflow Control
 * - need_manual_review=false: immediate webhook after provider result
 * - need_manual_review=true: intermediate status, no immediate webhook
 *
 * ### 2. Status Flow
 * - Provider APPROVED -> PROVIDER_APPROVED (when manual review required)
 * - Provider REJECTED -> PROVIDER_REJECTED (when manual review required)
 * - Provider ERROR -> PROVIDER_ERROR (when manual review required)
 *
 * ### 3. Manual Review Actions
 * - Admin can approve a PROVIDER_APPROVED profile -> APPROVED
 * - Admin can reject a PROVIDER_APPROVED profile (override) -> REJECTED
 * - Admin can approve a PROVIDER_REJECTED profile (override) -> APPROVED
 * - Review notes are required and stored
 *
 * ### 4. Webhook Behavior
 * - Webhook only sent after manual review when need_manual_review=true
 * - Webhook payload includes review information (notes, reviewer, timestamp)
 */
class ManualReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

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
            'meta' => [
                'service_provider' => 'test',
                'reference_id' => 'TEST-REF-001',
            ],
        ], $overrides);
    }

    public function test_profile_without_manual_review_sends_webhook_immediately(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => false,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Run the job - should immediately dispatch webhook
        $job = new TestKYCResultJob($profile->id, 'approved');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);

        // Webhook should be dispatched immediately
        Queue::assertPushed(SendKycWebhookJob::class, function ($job) use ($profile) {
            return $job->profileId === $profile->id;
        });
    }

    public function test_profile_with_manual_review_sets_provider_status_and_no_webhook(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        // Run the job - should set PROVIDER_APPROVED, not dispatch webhook
        $job = new TestKYCResultJob($profile->id, 'approved');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::PROVIDER_APPROVED, $profile->status);
        $this->assertTrue($profile->isAwaitingReview());

        // Webhook should NOT be dispatched yet
        Queue::assertNotPushed(SendKycWebhookJob::class);
    }

    public function test_manual_review_with_rejection_sets_provider_rejected_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
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

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::PROVIDER_REJECTED, $profile->status);

        Queue::assertNotPushed(SendKycWebhookJob::class);
    }

    public function test_manual_review_with_error_sets_provider_error_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PENDING,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $job = new TestKYCResultJob($profile->id, 'error', 'Test error');
        $job->handle();

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::PROVIDER_ERROR, $profile->status);

        Queue::assertNotPushed(SendKycWebhookJob::class);
    }

    public function test_admin_can_approve_profile_awaiting_review(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            KycStatuseEnum::APPROVED,
            'Reviewed and approved by admin',
            $admin
        );

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertEquals('Reviewed and approved by admin', $profile->review_notes);
        $this->assertEquals($admin->id, $profile->reviewed_by);
        $this->assertNotNull($profile->reviewed_at);
        $this->assertEquals(KycStatuseEnum::PROVIDER_APPROVED, $profile->provider_status);

        // Webhook should be dispatched after review
        Queue::assertPushed(SendKycWebhookJob::class, function ($job) use ($profile) {
            return $job->profileId === $profile->id;
        });
    }

    public function test_admin_can_reject_profile_that_provider_approved_override(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            KycStatuseEnum::REJECTED,
            'Rejected due to suspicious activity despite provider approval',
            $admin
        );

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::REJECTED, $profile->status);
        $this->assertEquals(KycStatuseEnum::PROVIDER_APPROVED, $profile->provider_status);

        Queue::assertPushed(SendKycWebhookJob::class);
    }

    public function test_admin_can_approve_profile_that_provider_rejected_override(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PROVIDER_REJECTED,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            KycStatuseEnum::APPROVED,
            'Manual verification confirmed identity is valid',
            $admin
        );

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertEquals(KycStatuseEnum::PROVIDER_REJECTED, $profile->provider_status);

        Queue::assertPushed(SendKycWebhookJob::class);
    }

    public function test_admin_can_resolve_profile_with_provider_error(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PROVIDER_ERROR,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            KycStatuseEnum::APPROVED,
            'Manual review after provider error - identity confirmed',
            $admin
        );

        $profile->refresh();
        $this->assertEquals(KycStatuseEnum::APPROVED, $profile->status);
        $this->assertEquals(KycStatuseEnum::PROVIDER_ERROR, $profile->provider_status);

        Queue::assertPushed(SendKycWebhookJob::class);
    }

    public function test_webhook_payload_includes_review_data(): void
    {
        Http::fake([
            '*' => Http::response(['received' => true], 200),
        ]);

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'webhook_url' => 'https://example.com/webhook',
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'provider' => 'test',
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
            'profile_data' => $this->getValidScreeningData(),
        ]);

        $workflowService = app(KycWorkflowService::class);
        $workflowService->processReview(
            $profile,
            KycStatuseEnum::APPROVED,
            'Approved after manual review',
            $admin
        );

        $profile->refresh();

        // Now dispatch the webhook job manually to verify payload
        $webhookJob = new SendKycWebhookJob($profile->id, [
            'review_data' => [
                'notes' => $profile->review_notes,
                'reviewed_by' => $admin->name,
                'reviewed_at' => $profile->reviewed_at->toIso8601String(),
                'original_provider_status' => $profile->provider_status->value,
            ],
        ]);
        $webhookJob->handle();

        // Verify webhook was sent with correct data
        Http::assertSent(function ($request) use ($admin, $profile) {
            $payload = $request->data();
            return $payload['payload']['review_notes'] === 'Approved after manual review'
                && $payload['payload']['reviewed_by'] === $admin->name
                && $payload['payload']['original_provider_status'] === 'provider_approved'
                && $payload['payload']['status'] === 'approved';
        });
    }

    public function test_kyc_status_enum_is_awaiting_review_helper(): void
    {
        $this->assertTrue(KycStatuseEnum::PROVIDER_APPROVED->isAwaitingReview());
        $this->assertTrue(KycStatuseEnum::PROVIDER_REJECTED->isAwaitingReview());
        $this->assertTrue(KycStatuseEnum::PROVIDER_ERROR->isAwaitingReview());

        $this->assertFalse(KycStatuseEnum::PENDING->isAwaitingReview());
        $this->assertFalse(KycStatuseEnum::APPROVED->isAwaitingReview());
        $this->assertFalse(KycStatuseEnum::REJECTED->isAwaitingReview());
        $this->assertFalse(KycStatuseEnum::ERROR->isAwaitingReview());
        $this->assertFalse(KycStatuseEnum::UNRESOLVED->isAwaitingReview());
    }

    public function test_kyc_workflow_service_resolve_status_with_manual_review(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'need_manual_review' => true,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::PENDING,
        ]);

        $workflowService = app(KycWorkflowService::class);

        $this->assertEquals(
            KycStatuseEnum::PROVIDER_APPROVED,
            $workflowService->resolveStatus($profile, KycStatuseEnum::APPROVED)
        );

        $this->assertEquals(
            KycStatuseEnum::PROVIDER_REJECTED,
            $workflowService->resolveStatus($profile, KycStatuseEnum::REJECTED)
        );

        $this->assertEquals(
            KycStatuseEnum::PROVIDER_ERROR,
            $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR)
        );
    }

    public function test_kyc_workflow_service_resolve_status_without_manual_review(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'need_manual_review' => false,
        ]);
        $profile = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::PENDING,
        ]);

        $workflowService = app(KycWorkflowService::class);

        $this->assertEquals(
            KycStatuseEnum::APPROVED,
            $workflowService->resolveStatus($profile, KycStatuseEnum::APPROVED)
        );

        $this->assertEquals(
            KycStatuseEnum::REJECTED,
            $workflowService->resolveStatus($profile, KycStatuseEnum::REJECTED)
        );
    }

    public function test_kyc_workflow_service_should_dispatch_webhook(): void
    {
        $user = User::factory()->create();
        $apiKeyWithReview = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'need_manual_review' => true,
        ]);
        $apiKeyWithoutReview = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'need_manual_review' => false,
        ]);

        $profileAwaitingReview = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKeyWithReview->id,
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
        ]);

        $profileFinalStatus = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKeyWithReview->id,
            'status' => KycStatuseEnum::APPROVED,
        ]);

        $profileNoReview = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKeyWithoutReview->id,
            'status' => KycStatuseEnum::APPROVED,
        ]);

        $workflowService = app(KycWorkflowService::class);

        // Should NOT dispatch when awaiting review
        $this->assertFalse($workflowService->shouldDispatchWebhook($profileAwaitingReview));

        // Should dispatch when final status reached
        $this->assertTrue($workflowService->shouldDispatchWebhook($profileFinalStatus));

        // Should dispatch when no manual review required
        $this->assertTrue($workflowService->shouldDispatchWebhook($profileNoReview));
    }

    public function test_profile_is_awaiting_review_helper(): void
    {
        $user = User::factory()->create();
        $apiKey = UserApiKey::factory()->create([
            'user_id' => $user->id,
            'need_manual_review' => true,
        ]);

        $profileAwaiting = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::PROVIDER_APPROVED,
        ]);

        $profileFinal = KYCProfile::factory()->create([
            'user_id' => $user->id,
            'user_api_key_id' => $apiKey->id,
            'status' => KycStatuseEnum::APPROVED,
        ]);

        $this->assertTrue($profileAwaiting->isAwaitingReview());
        $this->assertFalse($profileFinal->isAwaitingReview());
    }
}
