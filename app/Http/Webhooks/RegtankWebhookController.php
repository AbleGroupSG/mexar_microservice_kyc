<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\DTOs\Webhooks\DjkybDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SendKycWebhookJob;
use App\Models\CompanyKyb;
use App\Models\KYCProfile;
use App\Models\WebhookLog;
use App\Services\EFormAppService;
use App\Services\KYC\KycWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
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
        $data = request()->all();
        $dto = KycDTO::make($data);
        WebhookLog::saveRequest(WebhookLog::REGTANK, WebhookTypeEnum::KYC, $data);
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
        WebhookLog::saveRequest(WebhookLog::REGTANK, WebhookTypeEnum::fromString('djkyb'), $data);

        try {
            $this->EFormAppService->sendDjkyb($data);
        }catch (Throwable $e) {
            logger()->error('CompanyKyb not found', ['request_id' => $data['requestId']]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    public function liveness(): JsonResponse
    {
        $data = request()->all();
        WebhookLog::saveRequest(WebhookLog::REGTANK, WebhookTypeEnum::fromString('liveness'), $data);

        try {
            $this->EFormAppService->sendLiveness($data);
        }catch (Throwable $e) {
            logger()->error('CompanyKyb not found', ['request_id' => $data['requestId']]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }
}
