<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

            // Dispatch async job to send webhook to client's configured webhook URL
            SendKycWebhookJob::dispatch($profile->id);

        } catch (Exception $e) {
            Log::error('Test KYC verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $profile->status = KycStatuseEnum::ERROR;
            $profile->save();

            // Dispatch async job to send error webhook
            SendKycWebhookJob::dispatch(
                profileId: $profile->id,
                additionalData: ['error' => $e->getMessage()]
            );
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

        // Dispatch async job to send error webhook to client
        SendKycWebhookJob::dispatch(
            profileId: $profile->id,
            additionalData: [
                'error' => 'Test verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
            ]
        );
    }
}
