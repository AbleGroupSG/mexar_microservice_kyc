<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Services\KYC\GlairAI\GlairAIService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlairAIVerificationJob implements ShouldQueue
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
     */
    public function __construct(
        protected string $profileId,
        protected UserDataDTO $userDataDTO,
        protected array $data
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->find($this->profileId);

        if (!$profile) {
            Log::error('GlairAI verification: Profile not found', ['profile_id' => $this->profileId]);
            return;
        }

        try {
            $service = new GlairAIService();
            $response = $service->basicVerification($profile, $this->userDataDTO, $this->data);

            // Update profile with result
            $profile->provider_response_data = $response;
            $boolStatus = $response['verification_status'] ?? false;
            $profile->status = $boolStatus ? KycStatuseEnum::APPROVED : KycStatuseEnum::REJECTED;
            $profile->save();

            // Send webhook to the configured webhook URL
            if ($profile->apiKey && $profile->apiKey->webhook_url) {
                $this->sendWebhook($profile);
            } else {
                Log::warning('No webhook URL configured for API key', [
                    'profile_id' => $profile->id,
                    'user_api_key_id' => $profile->user_api_key_id,
                ]);
            }

        } catch (Exception $e) {
            Log::error('GlairAI verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            $profile->status = KycStatuseEnum::ERROR;
            $profile->save();

            // Send error webhook if webhook URL is configured
            if ($profile->apiKey && $profile->apiKey->webhook_url) {
                $this->sendWebhook($profile, $e->getMessage());
            }
        }
    }

    /**
     * Send webhook notification to client's webhook URL
     */
    private function sendWebhook(KYCProfile $profile, ?string $error = null): void
    {
        $webhookUrl = $profile->apiKey->webhook_url;

        $payload = [
            'event' => 'kyc.status.changed',
            'payload' => [
                'msa_reference_id' => $profile->id,
                'provider_reference_id' => $profile->provider_reference_id,
                'reference_id' => $this->userDataDTO->meta->reference_id,
                'platform' => KycServiceTypeEnum::GLAIR_AI,
                'status' => $profile->status,
                'verified' => $profile->status === KycStatuseEnum::APPROVED,
                'verified_at' => $profile->status === KycStatuseEnum::APPROVED
                    ? $profile->updated_at
                    : null,
                'rejected_at' => $profile->status === KycStatuseEnum::REJECTED
                    ? $profile->updated_at
                    : null,
                'message' => $error ?? 'KYC verification completed',
                'review_notes' => $error ? null : 'GlairAI identity verification',
                'failure_reason' => $error,
            ],
        ];

        Log::info('Sending GlairAI KYC webhook', [
            'profile_id' => $profile->id,
            'webhook_url' => $webhookUrl,
            'status' => $profile->status->value,
        ]);

        $response = Http::post($webhookUrl, $payload);

        if (!$response->successful()) {
            Log::error('Failed to send GlairAI KYC webhook', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);
        } else {
            Log::info('GlairAI KYC webhook sent successfully', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
            ]);
        }
    }

    /**
     * Handle a job failure after all retry attempts exhausted.
     *
     * This method is called when the job has permanently failed after all retry
     * attempts. It ensures the profile is marked as ERROR and a webhook is sent
     * to notify the client of the failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GlairAI verification job permanently failed', [
            'profile_id' => $this->profileId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->find($this->profileId);

        if (!$profile) {
            Log::error('Cannot update profile - profile not found', [
                'profile_id' => $this->profileId,
            ]);
            return;
        }

        // Update profile to ERROR status
        $profile->status = KycStatuseEnum::ERROR;
        $profile->provider_response_data = [
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'attempts' => $this->attempts(),
        ];
        $profile->save();

        // Send error webhook to client
        if ($profile->apiKey && $profile->apiKey->webhook_url) {
            try {
                $this->sendWebhook($profile, 'Verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage());
            } catch (\Throwable $webhookError) {
                Log::error('Failed to send error webhook after job failure', [
                    'profile_id' => $this->profileId,
                    'webhook_error' => $webhookError->getMessage(),
                ]);
            }
        }
    }
}
