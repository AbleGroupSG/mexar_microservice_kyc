<?php

namespace App\Jobs;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use App\Services\KYC\KycWorkflowService;
use App\Services\KYC\Sijitu\SijituService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            Log::info('Sijitu verification started', [
                'profile_id' => $this->profileId,
                'provider' => $profile->provider,
                'status' => $profile->status?->value,
                'request' => $this->sanitizeForLogs($this->data),
            ]);

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
        } catch (Throwable $e) {
            Log::error('Sijitu verification failed', [
                'profile_id' => $this->profileId,
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->truncateText($e->getTraceAsString(), 12000),
                'request' => $this->sanitizeForLogs($this->data),
            ]);

            $workflowService = app(KycWorkflowService::class);

            $profile->status = $workflowService->resolveStatus($profile, KycStatuseEnum::ERROR);
            $profile->provider_response_data = [
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $this->truncateText($e->getTraceAsString(), 12000),
                'request' => $this->sanitizeForLogs($this->data),
                'failed_at' => now()->toIso8601String(),
            ];
            $profile->save();

            if ($workflowService->shouldDispatchWebhook($profile)) {
                SendKycWebhookJob::dispatch(
                    profileId: $profile->id,
                    additionalData: ['error' => $e->getMessage()],
                );
            }

            return;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Sijitu verification job permanently failed', [
            'profile_id' => $this->profileId,
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
            'trace' => $this->truncateText($exception->getTraceAsString(), 12000),
            'request' => $this->sanitizeForLogs($this->data),
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
            'exception_class' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->truncateText($exception->getTraceAsString(), 12000),
            'request' => $this->sanitizeForLogs($this->data),
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

    private function sanitizeForLogs(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeForLogs($item);
            }

            return $sanitized;
        }

        if (! is_string($value)) {
            return $value;
        }

        if (! str_starts_with($value, 'data:image/')) {
            return $this->truncateText($value, 1000);
        }

        $parts = explode(',', $value, 2);
        if (count($parts) !== 2) {
            return $this->truncateText($value, 1000);
        }

        [$meta, $base64] = $parts;

        return sprintf(
            '%s,%s...[truncated %d chars]',
            $meta,
            substr($base64, 0, 64),
            max(strlen($base64) - 64, 0)
        );
    }

    private function truncateText(?string $text, int $limit): ?string
    {
        if ($text === null || strlen($text) <= $limit) {
            return $text;
        }

        $suffix = sprintf('...[truncated %d chars]', strlen($text) - $limit);
        $sliceLength = max($limit - strlen($suffix), 0);

        return substr($text, 0, $sliceLength) . $suffix;
    }
}
