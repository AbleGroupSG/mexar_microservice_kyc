<?php

namespace App\Http\Webhooks;

use App\DTO\Webhooks\KycDTO;
use App\Enums\WebhookTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\EntityKyc;
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
        $entityKyc = EntityKyc::query()->where('identity', $dto->requestId)->first();
        if($entityKyc) {
            $entityKyc->update([
                'status' => request()->input('status'),
                'risk_score' => request()->input('riskScore'),
                'last_checked_at' => now(),
            ]);
        } else {
            logger()->error('EntityKyc not found', ['identity' => $dto->requestId]);
        }

        //TODO Send data to MEXAR

        return response()->json(['status' => true], Response::HTTP_OK);
    }
}
