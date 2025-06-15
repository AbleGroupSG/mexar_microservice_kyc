<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\Enums\AppNameEnum;
use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\KYCProfile;
use App\Models\WebhookLog;
use App\Services\EFormAppService;
use App\Services\MexarAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

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
        $profile = KYCProfile::query()->where('provider_reference_id', $dto->requestId)->first();
        if($profile) {
            $status = $this->resolveStatus($dto->status);
            $profile->provider_response_data = $data;
            $profile->status = $status;
            $profile->save();

            $app = $profile->user;
            if ($app->name === AppNameEnum::E_FORM) {
                $this->EFormAppService->send($data);
            }

            if($app->name === AppNameEnum::MEXAR && $status !== KycStatuseEnum::UNRESOLVED) {
                $this->mexarAppService->send($profile, $dto, $status);
            }
        } else {
            logger()->error('Kyc profile not found', ['provider_reference_id' => $dto->requestId]);
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
}
