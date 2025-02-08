<?php

namespace App\Services\KYC;

use App\DTO\UserDataDTO;
use App\Services\KYC\Regtank\RegtankAuth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

readonly class RegtankService implements KYCServiceInterface
{
    public function __construct(
        private UserDataDTO $data
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function screen(): array
    {
        $accessToken = RegtankAuth::getToken();
        $data = $this->prepareData();
        $url = config('regtank.specific_server_url');
        return Http::withToken($accessToken)
            ->post("$url/v2/djkyc/exchange/input", $data)
            ->json();
    }

    private function prepareData():array
    {
        $dateOfBirth = $this->data->personal_info->date_of_birth
            ? Carbon::parse($this->data->personal_info->date_of_birth)
            : null;

        [$address1, $address2] = $this->prepareAddress();

        $assignee = config('regtank.assignee');
        return [
            'name' => $this->data->personal_info->first_name . ' ' . $this->data->personal_info->last_name,
            'profileNotes' => true,
            'referenceId' => $this->data->meta->reference_id,
            'occupationTitle' => true,
            'dateOfBirth' => $dateOfBirth?->day,
            'monthOfBirth' => $dateOfBirth?->month,
            'yearOfBirth' => $dateOfBirth?->year,
            'strictDateMatch' => true,
            'assignee' => $assignee,
            'email' => $this->data->contact->email,
            'phone' => $this->data->contact->phone,
            'address1' => $address1,
            'address2' => $address2,
            'gender' => Str::upper($this->data->personal_info->gender),
            'nationality' => $this->data->personal_info->nationality,
            'idIssuingCountry' => $this->data->identification->issuing_country,
            'enableOnGoingMonitoring' => true,
            'enableReScreening' => true,
        ];
    }

    private function prepareAddress(): array
    {
        $addressData = $this->data->address;
        $fullAddress = "$addressData->street, $addressData->city, $addressData->state, $addressData->postal_code, $addressData->country";

        $address1 = Str::limit($fullAddress, 255, '');

        $address2 = strlen($fullAddress) > 255 ? substr($fullAddress, 255) : '';
        return [$address1, $address2];
    }
}
