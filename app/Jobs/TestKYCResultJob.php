<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestKYCResultJob implements ShouldQueue
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
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $profileId,
        protected ?string $desiredStatus = null,
        protected ?string $error = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->find($this->profileId);

        if (! $profile) {
            Log::error('Test KYC verification: Profile not found', ['profile_id' => $this->profileId]);

            return;
        }

        try {
            // Parse UserDataDTO from profile_data (handle both string and array)
            $profileData = is_string($profile->profile_data)
                ? json_decode($profile->profile_data, true)
                : $profile->profile_data;
            $userDataDTO = UserDataDTO::from($profileData);

            // Determine final status (use desired status or default to approved for test mode)
            $finalStatus = $this->determineFinalStatus();

            // Simulate test verification process
            $response = [
                'test_mode' => true,
                'verification_status' => $finalStatus === KycStatuseEnum::APPROVED,
                'reason' => $this->getReasonForStatus($finalStatus),
                'timestamp' => now()->toIso8601String(),
            ];

            // Update profile with result
            $profile->provider_response_data = $response;
            $profile->status = $finalStatus;
            $profile->save();

            Log::info('Test KYC verification completed', [
                'profile_id' => $profile->id,
                'status' => $finalStatus->value,
            ]);

            // Send webhook to client's configured webhook URL
            if ($profile->apiKey && $profile->apiKey->webhook_url) {
                $this->sendWebhook($profile, $userDataDTO);
            } else {
                Log::warning('No webhook URL configured for API key', [
                    'profile_id' => $profile->id,
                    'user_api_key_id' => $profile->user_api_key_id,
                ]);
            }

        } catch (Exception $e) {
            Log::error('Test KYC verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $profile->status = KycStatuseEnum::ERROR;
            $profile->save();

            // Send error webhook if webhook URL is configured
            if ($profile->apiKey && $profile->apiKey->webhook_url) {
                // Re-parse UserDataDTO for error webhook
                try {
                    $profileData = is_string($profile->profile_data)
                        ? json_decode($profile->profile_data, true)
                        : $profile->profile_data;
                    $userDataDTO = UserDataDTO::from($profileData);
                    $this->sendWebhook($profile, $userDataDTO, $e->getMessage());
                } catch (Exception $parseError) {
                    Log::error('Failed to parse UserDataDTO for error webhook', [
                        'profile_id' => $this->profileId,
                        'error' => $parseError->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Determine the final status based on desired status or default
     */
    private function determineFinalStatus(): KycStatuseEnum
    {
        if ($this->error) {
            return KycStatuseEnum::ERROR;
        }

        if ($this->desiredStatus) {
            try {
                return KycStatuseEnum::from($this->desiredStatus);
            } catch (Exception $e) {
                Log::warning('Invalid desired status, defaulting to approved', [
                    'desired_status' => $this->desiredStatus,
                ]);
            }
        }

        // Default to approved for test mode
        return KycStatuseEnum::APPROVED;
    }

    /**
     * Get reason message for the given status
     */
    private function getReasonForStatus(KycStatuseEnum $status): string
    {
        return match ($status) {
            KycStatuseEnum::APPROVED => 'Test verification passed',
            KycStatuseEnum::REJECTED => 'Test verification rejected',
            KycStatuseEnum::ERROR => $this->error ?? 'Test verification error',
            KycStatuseEnum::UNRESOLVED => 'Test verification requires manual review',
            default => 'Test verification completed',
        };
    }

    /**
     * Send webhook notification to client's webhook URL
     */
    private function sendWebhook(KYCProfile $profile, UserDataDTO $userDataDTO, ?string $error = null): void
    {
        $webhookUrl = $profile->apiKey->webhook_url;

        $payload = [
            'event' => 'kyc.status.changed',
            'payload' => [
                'msa_reference_id' => $profile->id,
                'provider_reference_id' => $profile->provider_reference_id,
                'reference_id' => $userDataDTO->meta->reference_id ?? null,
                'platform' => KycServiceTypeEnum::TEST,
                'status' => $profile->status,
                'verified' => $profile->status === KycStatuseEnum::APPROVED,
                'verified_at' => $profile->status === KycStatuseEnum::APPROVED
                    ? $profile->updated_at
                    : null,
                'rejected_at' => $profile->status === KycStatuseEnum::REJECTED
                    ? $profile->updated_at
                    : null,
                'message' => $error ?? 'Test KYC verification completed',
                'review_notes' => $error ? null : 'Test mode verification',
                'failure_reason' => $error,
            ],
        ];

        Log::info('Sending Test KYC webhook', [
            'profile_id' => $profile->id,
            'webhook_url' => $webhookUrl,
            'status' => $profile->status->value,
        ]);

        $response = Http::post($webhookUrl, $payload);

        if (! $response->successful()) {
            Log::error('Failed to send Test KYC webhook', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);
        } else {
            Log::info('Test KYC webhook sent successfully', [
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
        Log::error('Test KYC verification job permanently failed', [
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
            'test_mode' => true,
        ];
        $profile->save();

        // Send error webhook to client
        if ($profile->apiKey && $profile->apiKey->webhook_url) {
            try {
                // Re-parse UserDataDTO for error webhook
                $profileData = is_string($profile->profile_data)
                    ? json_decode($profile->profile_data, true)
                    : $profile->profile_data;
                $userDataDTO = UserDataDTO::from($profileData);

                $this->sendWebhook(
                    $profile,
                    $userDataDTO,
                    'Test verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
                );
            } catch (\Throwable $webhookError) {
                Log::error('Failed to send error webhook after job failure', [
                    'profile_id' => $this->profileId,
                    'webhook_error' => $webhookError->getMessage(),
                ]);
            }
        }
    }
}
