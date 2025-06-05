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


    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->credentials = $this->getCredentials();
    }

    /**
     * @throws Exception
     */
    public function screen(UserDataDTO $userDataDTO): array
    {
        $profile = $this->createProfile($userDataDTO);
        $data = $this->prepareData($userDataDTO);
        $this->validateData($data);
        $status = $this->basicVerification($profile, $userDataDTO, $data);

        return $this->prepareResponse($userDataDTO, $status);
    }

    private function prepareData(UserDataDTO $userDataDTO): array
    {
        return [
            'nik' => $userDataDTO->identification->id_number,
            'name' => $userDataDTO->personal_info->first_name . ' ' . $userDataDTO->personal_info->last_name,
            'date_of_birth' => Carbon::make($userDataDTO->personal_info->date_of_birth)->format('d-m-Y'),
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
    public function basicVerification(KYCProfile $profile, UserDataDTO $userDataDTO, array $data): ?KycStatuseEnum
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
            $userDataDTO->uuid,
            $userDataDTO->meta->service_provider,
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

    private function createProfile(UserDataDTO $userDataDTO):KYCProfile
    {
        $profile = new KYCProfile();
        $profile->id = $userDataDTO->uuid;
        $profile->profile_data = $userDataDTO->toJson();
        $profile->provider = $userDataDTO->meta->service_provider;
        $profile->save();

        return $profile;
    }

    private function prepareResponse(UserDataDTO $userDataDTO, ?KycStatuseEnum $status): array
    {
        return [
            'uuid' => $userDataDTO->uuid,
            'provider' => $userDataDTO->meta->service_provider,
            'response' => $status->value,
        ];
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function readKTP(string $imagePath): KYCResponse
    {
        $url = $this->credentials->url . '/ocr/v1/ktp';
        $response = Http::withHeaders([
            'x-api-key' => $this->credentials->apiKey,
        ])
            ->withBasicAuth($this->credentials->username, $this->credentials->password)
            ->attach('image', file_get_contents($imagePath), basename($imagePath))
            ->timeout(300)
            ->post($url);

        if ($response->failed()) {
            $errorMessage = $response->body();
            throw new \Exception(
                'Check request ' . ' ' . $url . ' failed! Status: ' . $response->status() . '. Error: ' . $errorMessage,
                $response->status()
            );
        }

        if(empty($response->body()))
            throw new \Exception('Empty response from server', 500);

        if($read = $response->json()['read'] ?? null){
            return KYCResponse::make($read);
        }else{
            throw new \Exception('Failed to read image', 500);
        }
    }

    public function formatKtpResult(array $data): array
    {
        return [
            'fields' => [
                'national_id_number' => $data['nik']['value'] ?? null,
                'full_name' => $data['name']['value'] ?? null,
                'place_of_birth' => $data['birthPlace']['value'] ?? null,
                'date_of_birth' => $this->formatDate($data['birthDate']['value'] ?? null),
                'gender' => $this->mapGender($data['gender']['value'] ?? null),
                'address' => $data['address']['value'] ?? null,
                'rt_rw' => $data['neighborhoodAssociation']['value'] ?? null,
                'village' => $data['subdistrictVillage']['value'] ?? null,
                'district' => $data['district']['value'] ?? null,
                'religion' => strtolower($data['religion']['value'] ?? ''),
                'marital_status' => $this->mapMaritalStatus($data['maritalStatus']['value'] ?? null),
                'occupation' => strtolower($data['occupation']['value'] ?? ''),
                'citizenship' => $this->mapCitizenship($data['nationality']['value'] ?? null),
                'valid_until' => $this->mapValidUntil($data['validUntil']['value'] ?? null),
            ]
        ];
    }

    private function formatDate(?string $date): ?string
    {
        if (!$date) return null;
        $parsed = Carbon::parse($date);
        return $parsed?->format('Y-m-d');
    }

    private function mapGender(?string $value): ?string
    {
        return match (strtolower($value)) {
            'laki-laki' => 'male',
            'perempuan' => 'female',
            default => null,
        };
    }

    private function mapMaritalStatus(?string $value): ?string
    {
        return match (strtolower($value)) {
            'kawin' => 'married',
            'belum kawin' => 'single',
            default => strtolower($value),
        };
    }

    private function mapCitizenship(?string $value): ?string
    {
        return match (strtoupper($value)) {
            'WNI' => 'indonesian',
            'WNA' => 'foreign',
            default => strtolower($value),
        };
    }

    private function mapValidUntil(?string $value): ?string
    {
        return strtolower($value) === 'seumur hidup' ? 'lifetime' : $value;
    }

}
