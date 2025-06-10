<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\Enums\KycStatuseEnum;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\KYCProfile;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegtankWebhookController extends Controller
{
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



            //TODO Send data to MEXAR
        } else {
            logger()->error('Kyc profile not found', ['provider_reference_id' => $dto->requestId]);
        }

        return response()->json(['status' => true], Response::HTTP_OK);
    }

    private function resolveStatus(string $status): KycStatuseEnum
    {
        return match ($status) {
            'Approved', 'Score Generated' => KycStatuseEnum::APPROVED,
            'Rejected' => KycStatuseEnum::REJECTED,
            default => KycStatuseEnum::ERROR,
        };
    }
}
