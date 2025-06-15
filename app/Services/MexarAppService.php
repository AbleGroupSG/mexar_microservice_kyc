<?php

namespace App\Services;

use App\DTO\Webhooks\KycDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Models\KYCProfile;
use Illuminate\Support\Facades\Http;

class MexarAppService
{
    public function send(KYCProfile $profile, KycDTO $data, KycStatuseEnum $status): void
    {
        $url = config('app.mexar.url');
        $data = $this->prepareData($profile, $data, $status);
        $response = Http::post("$url/api/v1/webhook?platform=kyc", $data);

        if (!$response->successful()) {
            logger()->error('Failed to send KYC data to Mexar', [
                'status' => $response->status(),
                'response' => $response->json(),
                'data' => $data,
            ]);
        } else {
            logger()->info('KYC data sent to Mexar successfully', ['response' => $response->body()]);
        }
    }

    private function prepareData(KYCProfile $profile, KycDTO $data, KycStatuseEnum $status): array
    {
        return [
            'event' => 'kyc.status.changed',
            'payload' => [
                // microservice specific ID
                'msa_reference_id' => $profile->id,
                'provider_reference_id' => $profile->provider_reference_id,
                // the reference id when creating the KYC request and attaching under meta.reference_id
                'reference_id' => $data->referenceId,
                // the platform where the KYC is processed
                'platform' => KycServiceTypeEnum::REGTANK,
                // pending, approved, rejected, error
                // for new creating KYC requests or during screening, the status is always "pending"
                // for the final result, it can be "approved", "rejected"
                // error if the provider can not perform the KYC screening.
                'status' => $status,
//                'verified' => $data->,
                // required when verified is true
                'verified_at' => $status === KycStatuseEnum::APPROVED ? $data->timestamp : null,
                // required when verified is false
                'rejected_at' => $status === KycStatuseEnum::REJECTED ? $data->timestamp : null,
                // required for all status, a message describe the current status
                'message' => 'KYC verification completed risk level: ' . $data->riskLevel,
                // required if status is approved, rejected
                'review_notes' => '',
                // optional, only present on error status
                'failure_reason' => '',
            ],
        ];
    }
}
