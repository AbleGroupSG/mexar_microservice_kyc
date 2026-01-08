<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Services\KYC\GlairAI\GlairAIService;
use App\Services\KYC\KycWorkflowService;
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

        logger()->debug('Starting GlairAI verification job', [
            'profile_id' => $this->profileId,
            'user_data' => json_encode($this->userDataDTO->toArray()),
            'data' => json_encode($this->data ?? []),
        ]);
        if (!$profile) {
            Log::error('GlairAI verification: Profile not found', ['profile_id' => $this->profileId]);
            return;
        }

        try {
            $service = new GlairAIService();
            $workflowService = app(KycWorkflowService::class);

            $response = $service->basicVerification($profile, $this->userDataDTO, $this->data);

            // Determine provider result
            $boolStatus = $response['verification_status'] ?? false;
            $providerResult = $boolStatus ? KycStatuseEnum::APPROVED : KycStatuseEnum::REJECTED;

            // Update profile with result using workflow service to handle manual review
            $profile->provider_response_data = $response;
            $profile->status = $workflowService->resolveStatus($profile, $providerResult);
            $profile->save();

            // Only dispatch webhook if not awaiting manual review
            if ($workflowService->shouldDispatchWebhook($profile)) {
                SendKycWebhookJob::dispatch($profile->id);
            }

        } catch (Exception $e) {
            Log::error('GlairAI verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            $workflowService = app(KycWorkflowService::class);

            // Use workflow service to resolve ERROR status
            $profile->status = $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR);
            $profile->save();

            // Only dispatch webhook if not awaiting manual review
            if ($workflowService->shouldDispatchWebhook($profile)) {
                SendKycWebhookJob::dispatch(
                    profileId: $profile->id,
                    additionalData: ['error' => $e->getMessage()]
                );
            }
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

        $workflowService = app(KycWorkflowService::class);

        // Update profile to ERROR status using workflow service
        $profile->status = $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR);
        $profile->provider_response_data = [
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'attempts' => $this->attempts(),
        ];
        $profile->save();

        // Only dispatch webhook if not awaiting manual review
        if ($workflowService->shouldDispatchWebhook($profile)) {
            SendKycWebhookJob::dispatch(
                profileId: $profile->id,
                additionalData: [
                    'error' => 'Verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
                ]
            );
        }
    }
}
