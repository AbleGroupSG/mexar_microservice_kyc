<?php

namespace App\Services\KYC\GlairAI;

use App\DTO\GlarAICredentialsDTO;
use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Services\KYC\KYCServiceInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GlairAIService implements KYCServiceInterface
{
    private GlarAICredentialsDTO $credentials;
    private UserDataDTO $data;


    /**
     * @throws Exception
     */
    public function __construct(
        UserDataDTO $data
    )
    {
        $this->credentials = $this->getCredentials();
        $this->data = $data;
    }

    /**
     * @throws Exception
     */
    public function screen(): array
    {
        $profile = $this->createProfile();
        $data = $this->prepareData();
        $this->validateData($data);
        $status = $this->basicVerification($profile, $data);

        return $this->prepareResponse($status);
    }

    private function prepareData(): array
    {
        return [
            'nik' => $this->data->identification->id_number,
            'name' => $this->data->personal_info->first_name . ' ' . $this->data->personal_info->last_name,
            'date_of_birth' => Carbon::make($this->data->personal_info->date_of_birth)->format('d-m-Y'),
        ];
    }

    /**
     * @throws HttpException
     */
    private function validateData(array $data): void
    {
        if(!isset($data['nik'])) {
            throw new HttpException("'nik' field is required", Response::HTTP_BAD_REQUEST);
        }

        if(count($data) < 2) {
            throw new HttpException(
                'You must fill in at least one of the following: name, date_of_birth, liveness_fail_message, liveness_result, no_kk, mother_maiden_name, place_of_birth, address, gender, marital_status, job_type, province, city, district, subdistrict, rt, rw.',
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @throws Exception
     */
    private function getCredentials(): GlarAICredentialsDTO
    {
        $url = config('glair-ai.url');
        $apiKey = config('glair-ai.key');
        $username = config('glair-ai.username');
        $password = config('glair-ai.password');

        if(!$password || !$username || !$apiKey || !$url)
            throw new Exception('Glair AI credentials are not set');

        return GlarAICredentialsDTO::from([
            'url' => $url,
            'apiKey' => $apiKey,
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * @throws ConnectionException|HttpException
     */
    public function basicVerification(KYCProfile $profile, array $data): ?KycStatuseEnum
    {
        $url = $this->credentials->url . '/identity/v1/verification';

        $response = Http::withHeaders([
            'x-api-key' => $this->credentials->apiKey,
        ])
            ->withBasicAuth($this->credentials->username, $this->credentials->password)
            ->timeout(300)
            ->post($url, $data);

        ApiRequestLog::saveRequest(
            $data,
            $response->body(),
            $this->data->uuid,
            $this->data->meta->service_provider,
        );

        $responseData = $response->json() ?? [];
        if(!$response->successful()) {

            Log::error('Unsuccessful request', ['response' => $responseData]);
            $error = $responseData['error'] ?? 'An error occurred';

            throw new HttpException($response->status(), $error);
        }

        $boolStatus = $responseData['verification_status'] ?? false;
        $status = $boolStatus ? KycStatuseEnum::APPROVED : KycStatuseEnum::REJECTED;
        $profile->status = $status;
        $profile->save();

        return $profile->status;
    }

    private function createProfile():KYCProfile
    {
        $profile = new KYCProfile();
        $profile->id = $this->data->uuid;
        $profile->profile_data = $this->data->toJson();
        $profile->provider = $this->data->meta->service_provider;
        $profile->save();

        return $profile;
    }

    private function prepareResponse(?KycStatuseEnum $status): array
    {
        return [
            'uuid' => $this->data->uuid,
            'provider' => $this->data->meta->service_provider,
            'response' => $status->value,
        ];
    }

}
