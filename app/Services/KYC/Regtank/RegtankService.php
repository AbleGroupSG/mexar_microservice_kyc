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

/**
 * RegTank KYC Verification Service
 *
 * Handles KYC/AML screening using RegTank (Dow Jones) API for multiple countries.
 * Uses synchronous flow with webhook-based result notifications from provider.
 */
final class RegtankService implements KYCServiceInterface
{
    /**
     * Submit KYC screening request to RegTank.
     *
     * Creates a KYC profile with PENDING status and immediately sends verification
     * request to RegTank. Returns profile UUID while RegTank processes the request
     * and sends results via webhook.
     *
     * @param UserDataDTO $userDataDTO User data including personal info and identification
     * @param User $user The authenticated user
     * @param UserApiKey $userApiKey The API key used for this request
     * @return array Array containing 'identity' key with profile UUID
     * @throws ConnectionException If connection to RegTank fails
     * @throws HttpException If RegTank returns error response
     * @throws Throwable For other unexpected errors
     */
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        $accessToken = RegtankAuth::getToken();
        $profile = $this->createProfile($userDataDTO, $user, $userApiKey);
        $data = $this->prepareData($userDataDTO);
        $url = config('regtank.specific_server_url');

        $response = Http::withToken($accessToken)
            ->post("$url/v2/djkyc/exchange/input", $data);
        logger()->debug('Regtank DJKYC Response: ' . json_encode($response->json()));
        $profile->provider_reference_id = $response->json()['requestId'] ?? null;
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
            'identity' => $profile->id
        ];
    }

    /**
     * Create a new KYC profile with PENDING status.
     *
     * @param UserDataDTO $userDataDTO User data to store in profile
     * @param User $user The authenticated user
     * @param UserApiKey $userApiKey The API key used for this request
     * @return KYCProfile Created profile instance
     */
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

    /**
     * Prepare verification data for RegTank API.
     *
     * Transforms UserDataDTO into RegTank-specific format including name, DOB,
     * address, nationality, and other screening parameters. Date of birth is
     * split into day, month, and year components.
     *
     * @param UserDataDTO $userDataDTO User data to transform
     * @return array Formatted data for RegTank Dow Jones API
     */
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
            'gender' => $userDataDTO->personal_info->gender
                ? Str::upper($userDataDTO->personal_info->gender)
                : null,
            'nationality' => $userDataDTO->personal_info->nationality ?? null,
            'idIssuingCountry' => $userDataDTO->identification->issuing_country ?? null,
            'enableOnGoingMonitoring' => true,
            'enableReScreening' => true,
        ];
    }

    /**
     * Prepare address data for RegTank API.
     *
     * Concatenates all address components into a single string, filters out null values,
     * and splits into address1 (first 255 chars) and address2 (overflow) as required
     * by RegTank API format.
     *
     * @param UserDataDTO $userDataDTO User data containing address information
     * @return array Array containing [address1, address2] strings
     */
    private function prepareAddress(UserDataDTO $userDataDTO): array
    {
        $addressData = $userDataDTO->address;

        // Filter out null values to avoid malformed addresses
        $addressParts = array_filter([
            $addressData->address_line,
            $addressData->street,
            $addressData->city,
            $addressData->state,
            $addressData->postal_code,
            $addressData->country,
        ]);

        $fullAddress = implode(', ', $addressParts);

        $address1 = Str::limit($fullAddress, 255, '');

        $address2 = strlen($fullAddress) > 255 ? substr($fullAddress, 255) : '';
        return [$address1, $address2];
    }
}
