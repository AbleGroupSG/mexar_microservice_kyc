<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send KYC Webhook Notification Job
 *
 * Unified async job for sending KYC verification result webhooks to clients.
 * Handles all providers (RegTank, GlairAI, Test) with consistent retry logic
 * and error handling. Prevents blocking of incoming webhook processing.
 */
class SendKycWebhookJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param string $profileId The KYC profile UUID
     * @param array $additionalData Optional provider-specific data (e.g., RegTank DTO, error messages)
     */
    public function __construct(
        public string $profileId,
        public array $additionalData = []
    ) {}

    /**
     * Execute the job.
     *
     * Loads profile, builds webhook payload, and sends to client's configured
     * webhook URL with timeout and error handling. Automatically retries on
     * failure with exponential backoff.
     */
    public function handle(): void
    {
        $profile = KYCProfile::query()
            ->with(['apiKey', 'user', 'reviewer'])
            ->find($this->profileId);

        if (! $profile) {
            Log::error('SendKycWebhook: Profile not found', [
                'profile_id' => $this->profileId,
            ]);

            return;
        }

        // Check if webhook URL is configured
        if (! $profile->apiKey || ! $profile->apiKey->webhook_url) {
            Log::warning('SendKycWebhook: No webhook URL configured', [
                'profile_id' => $profile->id,
                'user_api_key_id' => $profile->user_api_key_id,
            ]);

            return;
        }

        $webhookUrl = $profile->apiKey->webhook_url;

        try {
            // Build webhook payload
            $payload = $this->buildPayload($profile);

            Log::info('SendKycWebhook: Sending webhook', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
                'status' => $profile->status->value,
                'attempt' => $this->attempts(),
            ]);

            // Build request headers with optional signature
            $headers = [
                'Content-Type' => 'application/json',
            ];
            $signatureKey = $profile->apiKey->signature_key;
            $payloadJson = json_encode($payload);

            if ($signatureKey) {
                $timestamp = time();
                $signature = hash_hmac('sha256', $timestamp . '.' . $payloadJson, $signatureKey);

                $headers['X-Webhook-Signature'] = $signature;
                $headers['X-Webhook-Timestamp'] = (string) $timestamp;

                Log::debug('SendKycWebhook: Signature generated', [
                    'profile_id' => $profile->id,
                    'timestamp' => $timestamp,
                ]);
            }

            // Send webhook with 30-second timeout
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->withBody($payloadJson, 'application/json')
                ->post($webhookUrl);

            if (! $response->successful()) {
                Log::error('SendKycWebhook: Failed to send webhook', [
                    'profile_id' => $profile->id,
                    'webhook_url' => $webhookUrl,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'attempt' => $this->attempts(),
                ]);

                // Throw exception to trigger retry
                throw new \Exception(
                    "Webhook failed with status {$response->status()}: {$response->body()}"
                );
            }

            Log::info('SendKycWebhook: Webhook sent successfully', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
                'attempt' => $this->attempts(),
            ]);

        } catch (\Throwable $e) {
            Log::error('SendKycWebhook: Exception during webhook send', [
                'profile_id' => $this->profileId,
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Build standardized webhook payload.
     *
     * Creates consistent payload structure across all providers including
     * verification status, timestamps, and provider-specific data.
     *
     * @param KYCProfile $profile The KYC profile
     * @return array Webhook payload
     */
    private function buildPayload(KYCProfile $profile): array
    {
        // Parse UserDataDTO from profile_data
        $profileData = is_string($profile->profile_data)
            ? json_decode($profile->profile_data, true)
            : $profile->profile_data;

        $userDataDTO = UserDataDTO::from($profileData);

        // Extract error message if present in additional data
        $errorMessage = $this->additionalData['error'] ?? null;

        // Extract provider-specific data (e.g., RegTank DTO)
        $providerData = $this->additionalData['provider_data'] ?? [];

        // Extract review data if present (from manual review workflow)
        $reviewData = $this->additionalData['review_data'] ?? null;

        // Determine timestamps based on status
        $verifiedAt = $profile->status === KycStatuseEnum::APPROVED
            ? $this->getTimestamp($profile, $providerData)
            : null;

        $rejectedAt = $profile->status === KycStatuseEnum::REJECTED
            ? $this->getTimestamp($profile, $providerData)
            : null;

        // Build failure reason for rejected status
        $failureReason = null;
        if ($profile->status === KycStatuseEnum::REJECTED) {
            $failureReason = $errorMessage
                ?? $providerData['status'] ?? 'Verification rejected';
        }

        // Build message
        $message = $errorMessage ?? $this->buildMessage($profile, $providerData);

        // Build review notes - prioritize review data from manual review,
        // then profile review_notes, then provider status
        $reviewNotes = $reviewData['notes']
            ?? $profile->review_notes
            ?? $providerData['status']
            ?? ($errorMessage ? null : 'Verification completed');

        return [
            'event' => 'kyc.status.changed',
            'payload' => [
                'msa_reference_id' => $profile->id,
                'provider_reference_id' => $profile->provider_reference_id,
                'reference_id' => $userDataDTO->meta->reference_id ?? null,
                'platform' => config('app.mexar.webhook_platform_slug', 'kyc-msa'),
                'status' => $profile->status->value,
                'verified' => $profile->status === KycStatuseEnum::APPROVED,
                'verified_at' => $verifiedAt,
                'rejected_at' => $rejectedAt,
                'message' => $message,
                'review_notes' => $reviewNotes,
                'failure_reason' => $failureReason,
                // Manual review fields
                'reviewed_by' => $reviewData['reviewed_by'] ?? $profile->reviewer?->name,
                'reviewed_at' => $reviewData['reviewed_at'] ?? $profile->reviewed_at?->toIso8601String(),
                'original_provider_status' => $reviewData['original_provider_status'] ?? $profile->provider_status?->value,
            ],
        ];
    }

    /**
     * Get timestamp for verification event.
     *
     * Uses provider timestamp if available, otherwise falls back to profile updated_at.
     *
     * @param KYCProfile $profile The KYC profile
     * @param array $providerData Provider-specific data
     * @return string|null ISO8601 timestamp
     */
    private function getTimestamp(KYCProfile $profile, array $providerData): ?string
    {
        // Try provider timestamp first (e.g., RegTank DTO)
        if (isset($providerData['timestamp'])) {
            return $providerData['timestamp'];
        }

        // Fall back to profile updated_at
        return $profile->updated_at?->toIso8601String();
    }

    /**
     * Build message based on profile status and provider data.
     *
     * @param KYCProfile $profile The KYC profile
     * @param array $providerData Provider-specific data
     * @return string Message text
     */
    private function buildMessage(KYCProfile $profile, array $providerData): string
    {
        // Use provider-specific message if available (e.g., RegTank risk level)
        if (isset($providerData['riskLevel'])) {
            return "KYC verification completed risk level: {$providerData['riskLevel']}";
        }

        // Default messages by status
        return match ($profile->status) {
            KycStatuseEnum::APPROVED => 'KYC verification approved',
            KycStatuseEnum::REJECTED => 'KYC verification rejected',
            KycStatuseEnum::ERROR => 'KYC verification error',
            KycStatuseEnum::UNRESOLVED => 'KYC verification requires manual review',
            default => 'KYC verification completed',
        };
    }

    /**
     * Handle a job failure after all retry attempts exhausted.
     *
     * This method is called when the job has permanently failed after all retry
     * attempts. It logs the permanent failure for monitoring and alerting.
     *
     * @param \Throwable $exception The exception that caused the failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('SendKycWebhook: Webhook delivery permanently failed', [
            'profile_id' => $this->profileId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'additional_data' => $this->additionalData,
        ]);

        // TODO: Consider adding admin notification or marking profile with webhook_failed flag
        // This allows manual intervention or retry via admin dashboard
    }
}
