<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendKycWebhookJob;
use App\Models\KYCProfile;
use App\Models\WebhookLog;
use App\Services\EFormAppService;
use App\Services\KYC\KycWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegtankWebhookController extends Controller
{
    public function __construct(
        protected EFormAppService $EFormAppService,
    ) {}

    public function kyc(): JsonResponse
    {
        return $this->processKycWebhook(WebhookTypeEnum::KYC);
    }

    public function djkyc(): JsonResponse
    {
        return $this->processKycWebhook(WebhookTypeEnum::DJKYC);
    }

    private function processKycWebhook(WebhookTypeEnum $webhookType): JsonResponse
    {
        $data = request()->all();
        $dto = KycDTO::make($data);

        $log = WebhookLog::saveRequestOnce(WebhookLog::REGTANK, $webhookType, $dto->requestId, $data);

        if (! $log) {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        $profile = KYCProfile::query()
            ->with(['apiKey', 'user'])
            ->where('provider_reference_id', $dto->requestId)
            ->first();

        if ($profile) {
            $workflowService = app(KycWorkflowService::class);

            // Get provider result and resolve to appropriate status
            $providerResult = $this->resolveStatus($dto->status);
            $profile->provider_response_data = $data;
            $profile->status = $workflowService->resolveStatus($profile, $providerResult);
            $profile->save();

            // Only dispatch webhook if not awaiting manual review
            if ($workflowService->shouldDispatchWebhook($profile)) {
                SendKycWebhookJob::dispatch(
                    profileId: $profile->id,
                    additionalData: [
                        'provider_data' => [
                            'status' => $dto->status,
                            'riskLevel' => $dto->riskLevel,
                            'timestamp' => $dto->timestamp,
                        ],
                    ]
                );
            }
        } else {
            Log::error('KYC profile not found', ['provider_reference_id' => $dto->requestId]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    private function resolveStatus(string $status): KycStatuseEnum
    {
        return match ($status) {
            'Approved' => KycStatuseEnum::APPROVED,
            'Rejected', 'Unresolved', 'No Match', 'Positive Match' => KycStatuseEnum::REJECTED,
            default => KycStatuseEnum::UNRESOLVED,
        };
    }

    public function djkyb(): JsonResponse
    {
        $data = request()->all();

        $log = WebhookLog::saveRequestOnce(WebhookLog::REGTANK, WebhookTypeEnum::fromString('djkyb'), $data['requestId'], $data);

        if (! $log) {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        try {
            $this->EFormAppService->sendDjkyb($data);
        } catch (Throwable $e) {
            logger()->error('CompanyKyb not found', ['request_id' => $data['requestId']]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function liveness(): JsonResponse
    {
        $data = request()->all();

        $log = WebhookLog::saveRequestOnce(WebhookLog::REGTANK, WebhookTypeEnum::fromString('liveness'), $data['requestId'], $data);

        if (! $log) {
            return response()->json(['status' => true], Response::HTTP_OK);
        }

        try {
            $this->EFormAppService->sendLiveness($data);
        } catch (Throwable $e) {
            logger()->error('CompanyKyb not found', ['request_id' => $data['requestId']]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }
}
