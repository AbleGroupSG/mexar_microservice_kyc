<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\DTOs\Webhooks\DjkybDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CompanyKyb;
use App\Models\KYCProfile;
use App\Models\WebhookLog;
use App\Services\EFormAppService;
use App\Services\MexarAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegtankWebhookController extends Controller
{
    public function __construct(
        protected MexarAppService $mexarAppService,
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
            $status = $this->resolveStatus($dto->status);
            $profile->provider_response_data = $data;
            $profile->status = $status;
            $profile->save();

            // Send webhook to the configured webhook URL for this API key
            if ($profile->apiKey && $profile->apiKey->webhook_url) {
                $this->sendWebhook($profile, $dto, $status);
            } else {
                Log::warning('No webhook URL configured for API key', [
                    'profile_id' => $profile->id,
                    'user_api_key_id' => $profile->user_api_key_id,
                ]);
            }
        } else {
            Log::error('KYC profile not found', ['provider_reference_id' => $dto->requestId]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    private function sendWebhook(KYCProfile $profile, KycDTO $dto, KycStatuseEnum $status): void
    {
        $webhookUrl = $profile->apiKey->webhook_url;

        $payload = [
            'event' => 'kyc.status.changed',
            'payload' => [
                'msa_reference_id' => $profile->id,
                'provider_reference_id' => $profile->provider_reference_id,
                'reference_id' => $dto->referenceId,
                'platform' => KycServiceTypeEnum::REGTANK,
                'status' => $status,
                'verified' => $status === KycStatuseEnum::APPROVED,
                'verified_at' => $status === KycStatuseEnum::APPROVED ? $dto->timestamp : null,
                'rejected_at' => $status === KycStatuseEnum::REJECTED ? $dto->timestamp : null,
                'message' => 'KYC verification completed risk level: ' . $dto->riskLevel,
                'review_notes' => $dto->status,
                'failure_reason' => $status == KycStatuseEnum::REJECTED ? $dto->status : null,
            ],
        ];

        Log::info('Sending KYC webhook', [
            'profile_id' => $profile->id,
            'webhook_url' => $webhookUrl,
            'status' => $status->value,
        ]);

        $response = Http::post($webhookUrl, $payload);

        if (!$response->successful()) {
            Log::error('Failed to send KYC webhook', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);
        } else {
            Log::info('KYC webhook sent successfully', [
                'profile_id' => $profile->id,
                'webhook_url' => $webhookUrl,
            ]);
        }
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
