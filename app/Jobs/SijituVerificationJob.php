<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Services\KYC\KycWorkflowService;
use App\Services\KYC\Sijitu\SijituService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SijituVerificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public int $backoff = 60;

    public function __construct(
        protected string $profileId,
        protected UserDataDTO $userDataDTO,
        protected array $data
    ) {}

    public function handle(): void
    {
        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->find($this->profileId);

        if (! $profile) {
            Log::error('Sijitu verification: Profile not found', ['profile_id' => $this->profileId]);
            return;
        }

        try {
            $service = new SijituService();
            $workflowService = app(KycWorkflowService::class);

            $response = $service->basicVerification($profile, $this->userDataDTO, $this->data);
            $providerResult = $service->resolveProviderResult($response);
            $reason = $service->resolveReason($response);

            $profile->provider_reference_id = (string) ($response['rq_uuid'] ?? $profile->provider_reference_id);
            $profile->provider_response_data = $response;
            $profile->status = $workflowService->resolveStatus($profile, $providerResult);
            $profile->save();

            if ($workflowService->shouldDispatchWebhook($profile)) {
                $additionalData = [
                    'reason' => $reason,
                    'provider_data' => ['status' => $reason],
                ];

                if ($providerResult === KycStatuseEnum::ERROR) {
                    $additionalData['error'] = $reason;
                }

                SendKycWebhookJob::dispatch(
                    profileId: $profile->id,
                    additionalData: $additionalData,
                );
            }
        } catch (Exception $e) {
            Log::error('Sijitu verification failed', [
                'profile_id' => $this->profileId,
                'error' => $e->getMessage(),
            ]);

            $workflowService = app(KycWorkflowService::class);

            $profile->status = $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR);
            $profile->provider_response_data = [
                'error' => $e->getMessage(),
            ];
            $profile->save();

            if ($workflowService->shouldDispatchWebhook($profile)) {
                SendKycWebhookJob::dispatch(
                    profileId: $profile->id,
                    additionalData: ['error' => $e->getMessage()],
                );
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sijitu verification job permanently failed', [
            'profile_id' => $this->profileId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->find($this->profileId);

        if (! $profile) {
            Log::error('Cannot update Sijitu profile - profile not found', [
                'profile_id' => $this->profileId,
            ]);
            return;
        }

        $workflowService = app(KycWorkflowService::class);

        $profile->status = $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR);
        $profile->provider_response_data = [
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'attempts' => $this->attempts(),
        ];
        $profile->save();

        if ($workflowService->shouldDispatchWebhook($profile)) {
            SendKycWebhookJob::dispatch(
                profileId: $profile->id,
                additionalData: [
                    'error' => 'Verification failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
                ]
            );
        }
    }
}
