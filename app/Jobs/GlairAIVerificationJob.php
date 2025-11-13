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

            // Dispatch async job to send webhook to client's configured webhook URL
            SendKycWebhookJob::dispatch($profile->id);

        } catch (Exception $e) {
            Log::error('GlairAI verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
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

        // Dispatch async job to send error webhook to client
        SendKycWebhookJob::dispatch(
            profileId: $profile->id,
            additionalData: [
                'error' => 'Verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
            ]
        );
    }
}
