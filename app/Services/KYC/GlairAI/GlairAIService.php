<?php

namespace App\Services\KYC\GlairAI;

use App\DTO\GlarAICredentialsDTO;
use App\DTO\UserDataDTO;
use App\Enums\KycServiceTypeEnum;
use App\Enums\KycStatuseEnum;
use App\Jobs\GlairAIVerificationJob;
use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\KYC\KYCServiceInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * GlairAI KYC Verification Service
 *
 * Handles identity verification for Indonesian users using GlairAI API.
 * Follows async-first pattern with background job processing.
 */
class GlairAIService implements KYCServiceInterface
{
    const PASSPORT_URL = '/ocr/v1/passport';
    const KTP_URL = '/ocr/v1/ktp';
    private GlarAICredentialsDTO $credentials;

    /**
     * Initialize GlairAI service with credentials from config.
     *
     * @throws Exception If GlairAI credentials are not configured
     */
    public function __construct()
    {
        $this->credentials = $this->getCredentials();
    }

    /**
     * Submit KYC screening request (async processing via background job).
     *
     * Creates a KYC profile with PENDING status and dispatches a background job
     * for actual verification. Returns profile UUID immediately.
     *
     * @param UserDataDTO $userDataDTO User data including personal info and identification
     * @param User $user The authenticated user
     * @param UserApiKey $userApiKey The API key used for this request
     * @return array Array containing 'identity' key with profile UUID
     * @throws Exception If profile creation or job dispatch fails
     */
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        // Create profile with PENDING status
        $profile = $this->createProfile($userDataDTO, $user, $userApiKey);
        $data = $this->prepareData($userDataDTO);
        $this->validateData($data);

        // Dispatch async job to process verification
        GlairAIVerificationJob::dispatch($profile->id, $userDataDTO, $data)
            ->delay(now()->addSeconds(2));

        // Return only reference ID (consistent with RegTank)
        return ['identity' => $profile->id];
    }

    /**
     * Prepare verification data for GlairAI API.
     *
     * Transforms UserDataDTO into GlairAI-specific format with NIK, name, and DOB.
     *
     * @param UserDataDTO $userDataDTO User data to transform
     * @return array Formatted data for GlairAI API
     */
    private function prepareData(UserDataDTO $userDataDTO): array
    {
        return [
            'nik' => $userDataDTO->identification->id_number,
            'name' => $userDataDTO->personal_info->first_name . ' ' . $userDataDTO->personal_info->last_name,
            'date_of_birth' => $userDataDTO->personal_info->date_of_birth
                ? Carbon::make($userDataDTO->personal_info->date_of_birth)->format('d-m-Y')
                : null,
        ];
    }

    /**
     * Validate prepared data meets GlairAI requirements.
     *
     * Ensures NIK is present and at least one additional field is provided.
     *
     * @param array $data Prepared verification data
     * @throws HttpException If validation fails
     */
    private function validateData(array $data): void
    {
        if(!isset($data['nik'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, "'nik' field is required");
        }

        if(count($data) < 2) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'You must fill in at least one of the following: name, date_of_birth, liveness_fail_message, liveness_result, no_kk, mother_maiden_name, place_of_birth, address, gender, marital_status, job_type, province, city, district, subdistrict, rt, rw.'
            );
        }
    }

    /**
     * Load GlairAI credentials from configuration.
     *
     * @return GlarAICredentialsDTO GlairAI API credentials
     * @throws Exception If any required credential is missing
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
     * Perform identity verification via GlairAI API.
     *
     * Sends verification data to GlairAI and logs the request/response.
     * Called by GlairAIVerificationJob in background processing.
     *
     * @param KYCProfile $profile The KYC profile being verified
     * @param UserDataDTO $userDataDTO User data for logging
     * @param array $data Prepared verification data
     * @return array GlairAI API response data
     * @throws ConnectionException If connection to GlairAI fails
     * @throws HttpException If GlairAI returns error response
     */
    public function basicVerification(KYCProfile $profile, UserDataDTO $userDataDTO, array $data): array
    {
        $url = $this->credentials->url . '/identity/v1/verification';

        $response = Http::withHeaders([
            'x-api-key' => $this->credentials->apiKey,
        ])
            ->withBasicAuth($this->credentials->username, $this->credentials->password)
            ->timeout(300)
            ->post($url, $data);

        ApiRequestLog::saveRequest(
            data:  $data,
            response: $response->body(),
            request_uuid: $userDataDTO->uuid,
            provider: $userDataDTO->meta->service_provider,
        );

        $responseData = $response->json() ?? [];
        if(!$response->successful()) {
            Log::error('Unsuccessful request', ['response' => $responseData]);
            $error = $responseData['error'] ?? 'An error occurred';
            throw new HttpException($response->status(), $error);
        }

        // Status will be updated by GlairAIVerificationJob, no need to update here
        return $responseData;
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
        $profile->user_id = $user->id;
        $profile->user_api_key_id = $userApiKey->id;
        $profile->provider = $userDataDTO->meta->service_provider;
        $profile->status = KycStatuseEnum::PENDING;
        $profile->save();

        return $profile;
    }

    /**
     * Prepare response format (legacy method).
     *
     * @param UserDataDTO $userDataDTO User data
     * @param KycStatuseEnum|null $status Verification status
     * @return array Formatted response
     */
    private function prepareResponse(UserDataDTO $userDataDTO, ?KycStatuseEnum $status): array
    {
        return [
            'uuid' => $userDataDTO->uuid,
            'provider' => $userDataDTO->meta->service_provider,
            'response' => $status->value,
        ];
    }

    /**
     * Perform OCR on document image (KTP or Passport).
     *
     * @param string $ocrType OCR endpoint type (e.g., '/ocr/v1/ktp')
     * @param string $imagePath Path to image file
     * @return OCRResponse Parsed OCR results
     * @throws ConnectionException If connection to GlairAI fails
     * @throws Exception If OCR processing fails or response is invalid
     */
    public function readOCR(string $ocrType, string $imagePath): OCRResponse
    {
        $url = $this->credentials->url . $ocrType;
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
            return OCRResponse::make($read);
        }else{
            throw new \Exception('Failed to read image', 500);
        }
    }

    /**
     * Format OCR result data into standardized structure.
     *
     * Transforms GlairAI OCR response into consistent field names and values.
     *
     * @param array $data Raw OCR data from GlairAI
     * @return array Formatted data with standardized field names
     */
    public function formatResult(array $data): array
    {
        return [
            'fields' => [
                'national_id_number' => $data['nik'] ?? null,
                'full_name' => $data['name'] ?? null,
                'place_of_birth' => $data['birthPlace'] ?? null,
                'date_of_birth' => $this->formatDate($data['birthDate'] ?? null),
                'gender' => $this->mapGender($data['gender'] ?? null),
                'address' => $data['address'] ?? null,
                'rt_rw' => $data['neighborhoodAssociation'] ?? null,
                'village' => $data['subdistrictVillage'] ?? null,
                'district' => $data['district'] ?? null,
                'religion' => strtolower($data['religion'] ?? ''),
                'marital_status' => $this->mapMaritalStatus($data['maritalStatus'] ?? null),
                'occupation' => strtolower($data['occupation'] ?? ''),
                'citizenship' => $this->mapCitizenship($data['nationality'] ?? null),
                'valid_until' => $this->mapValidUntil($data['validUntil'] ?? null),
            ]
        ];
    }

    /**
     * Map Indonesian gender values to English.
     *
     * @param string|null $value Gender value from GlairAI (e.g., 'laki-laki', 'perempuan')
     * @return string|null 'male', 'female', or null
     */
    private function mapGender(?string $value): ?string
    {
        return match (strtolower($value)) {
            'laki-laki', 'm' => 'male',
            'perempuan', 'f' => 'female',
            default => null,
        };
    }

    /**
     * Map Indonesian marital status values to English.
     *
     * @param string|null $value Marital status from GlairAI (e.g., 'kawin', 'belum kawin')
     * @return string|null 'married', 'single', or lowercased original value
     */
    private function mapMaritalStatus(?string $value): ?string
    {
        return match (strtolower($value)) {
            'kawin' => 'married',
            'belum kawin' => 'single',
            default => strtolower($value),
        };
    }

    /**
     * Map Indonesian citizenship abbreviations to English.
     *
     * @param string|null $value Citizenship from GlairAI (e.g., 'WNI', 'WNA')
     * @return string|null 'indonesian', 'foreign', or lowercased original value
     */
    private function mapCitizenship(?string $value): ?string
    {
        return match (strtoupper($value)) {
            'WNI' => 'indonesian',
            'WNA' => 'foreign',
            default => strtolower($value),
        };
    }

    /**
     * Map Indonesian 'valid until' values to English.
     *
     * @param string|null $value Valid until from GlairAI (e.g., 'seumur hidup')
     * @return string|null 'lifetime' for permanent IDs, or original value
     */
    private function mapValidUntil(?string $value): ?string
    {
        return strtolower($value) === 'seumur hidup' ? 'lifetime' : $value;
    }

    /**
     * Format date string to YYYY-MM-DD format.
     *
     * Safely parses various date formats and converts to standard format.
     * Returns original value if parsing fails.
     *
     * @param string|null $value Date string to format
     * @return string|null Formatted date or null
     */
    public function formatDate(?string $value): string|null
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Throwable $e) {
            Log::warning('safeFormatDate: Failed to parse date', [
                'input' => $value,
                'error' => $e->getMessage(),
            ]);

            return $value;
        }
    }

}
