<?php

namespace App\Services\KYC\Regtank;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\KYC\KYCServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class RegtankService implements KYCServiceInterface
{
    /**
     * @throws ConnectionException|Throwable|HttpException
     */
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        $accessToken = RegtankAuth::getToken();
        $profile = $this->createProfile($userDataDTO, $user, $userApiKey);
        $data = $this->prepareData($userDataDTO);
        $url = config('regtank.specific_server_url');

        $response = Http::withToken($accessToken)
            ->post("$url/v2/djkyc/exchange/input", $data);

        $profile->provider_reference_id = $response->body();
        $profile->save();

        ApiRequestLog::saveRequest(
            $data,
            $response->body(),
            $userDataDTO->uuid,
            $userDataDTO->meta->service_provider,
        );

        $responseData = $response->json() ?? [];
        if(!$response->successful()) {

            Log::error('Unsuccessful request', ['response' => $responseData]);
            $error = $responseData['error'] ?? 'An error occurred';

            throw new HttpException($response->status(), $error);
        }

        return [
            'identity' => $response->body()
        ];
    }

    private function createProfile(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): KYCProfile
    {
        $profile = new KYCProfile();
        $profile->id = $userDataDTO->uuid;
        $profile->profile_data = $userDataDTO->toJson();
        $profile->provider = $userDataDTO->meta->service_provider;
        $profile->user_id = $user->id;
        $profile->user_api_key_id = $userApiKey->id;
        $profile->status = KycStatuseEnum::PENDING;
        $profile->save();

        return $profile;
    }

    private function prepareData(UserDataDTO $userDataDTO):array
    {
        $dateOfBirth = $userDataDTO->personal_info->date_of_birth
            ? Carbon::parse($userDataDTO->personal_info->date_of_birth)
            : null;

        [$address1, $address2] = $this->prepareAddress($userDataDTO);

        $assignee = config('regtank.assignee');
        return [
            'name' => $userDataDTO->personal_info->first_name . ' ' . $userDataDTO->personal_info->last_name,
            'profileNotes' => true,
            'referenceId' => $userDataDTO->meta->reference_id,
            'occupationTitle' => true,
            'dateOfBirth' => $dateOfBirth?->day,
            'monthOfBirth' => $dateOfBirth?->month,
            'yearOfBirth' => $dateOfBirth?->year,
            'strictDateMatch' => true,
            'assignee' => $assignee,
            'email' => $userDataDTO->contact->email ?? null,
            'phone' => $userDataDTO->contact->phone ?? null,
            'address1' => $address1,
            'address2' => $address2,
            'gender' => Str::upper($userDataDTO->personal_info->gender) ?? null,
            'nationality' => $userDataDTO->personal_info->nationality ?? null,
            'idIssuingCountry' => $userDataDTO->identification->issuing_country ?? null,
            'enableOnGoingMonitoring' => true,
            'enableReScreening' => true,
        ];
    }

    private function prepareAddress(UserDataDTO $userDataDTO): array
    {
        $addressData = $userDataDTO->address;
        $fullAddress = "$addressData->address_line, $addressData->street, $addressData->city, $addressData->state, $addressData->postal_code, $addressData->country";

        $address1 = Str::limit($fullAddress, 255, '');

        $address2 = strlen($fullAddress) > 255 ? substr($fullAddress, 255) : '';
        return [$address1, $address2];
    }
}
