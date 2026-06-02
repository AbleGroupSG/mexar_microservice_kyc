<?php

namespace App\Services\KYC\Sijitu;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Jobs\SijituVerificationJob;
use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\KYC\KYCServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SijituService implements KYCServiceInterface
{
    private const SERVICE_NAME = 'BIOMETRIC';

    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        $profile = $this->createProfile($userDataDTO, $user, $userApiKey);
        $data = $this->prepareData($userDataDTO);
        $this->validateData($data);

        SijituVerificationJob::dispatch($profile->id, $userDataDTO, $data)
            ->delay(now()->addSeconds(2));

        return ['identity' => $profile->id];
    }

    public function basicVerification(KYCProfile $profile, UserDataDTO $userDataDTO, array $data): array
    {
        $this->ensureConfigured();

        $requestData = [
            ...$data,
            'signature' => $this->generateSignature($data),
        ];

        $response = Http::withHeaders([
            'Authorization' => config('sijitu.authorization'),
            'Accept' => 'application/json',
        ])
            ->withBasicAuth(
                config('sijitu.username'),
                config('sijitu.password')
            )
            ->asForm()
            ->timeout(300)
            ->post(rtrim((string) config('sijitu.url'), '/') . '/cdd/sijitu/biometric', $requestData);

        ApiRequestLog::saveRequest(
            data: $requestData,
            response: $response->body(),
            request_uuid: $userDataDTO->uuid,
            provider: $userDataDTO->meta->service_provider,
        );

        $responseData = $response->json() ?? [];

        if (! $response->successful()) {
            $error = $responseData['error_message'] ?? $response->body() ?: 'Unable to verify biometric data';
            throw new HttpException($response->status(), $error);
        }

        return $responseData;
    }

    public function resolveProviderResult(array $response): KycStatuseEnum
    {
        if (($response['error_code'] ?? null) !== '0000') {
            return KycStatuseEnum::ERROR;
        }

        if ($this->isFullyVerified($response)) {
            return KycStatuseEnum::APPROVED;
        }

        return KycStatuseEnum::REJECTED;
    }

    public function resolveReason(array $response): string
    {
        $reason = trim((string) ($response['reason'] ?? $response['error_message'] ?? ''));
        if ($reason !== '') {
            return $reason;
        }

        $failedChecks = $this->getFailedChecks($response);
        if ($failedChecks !== []) {
            return 'Verification failed: ' . implode(', ', $failedChecks);
        }

        return 'Biometric verification failed';
    }

    private function prepareData(UserDataDTO $userDataDTO): array
    {
        return [
            'rq_uuid' => $userDataDTO->uuid,
            'rq_datetime' => now()->format('Y-m-d H:i:s'),
            'sender_id' => (string) config('sijitu.sender_id'),
            'user_id' => trim((string) config('sijitu.user_id')),
            'email' => trim((string) ($userDataDTO->contact?->email ?? '')),
            'phone_number' => $this->normalizePhoneNumber($userDataDTO->contact?->phone),
            'organization_id' => (string) config('sijitu.organization_id'),
            'nama_lengkap' => trim($userDataDTO->personal_info->first_name . ' ' . $userDataDTO->personal_info->last_name),
            'nomor_identitas' => $userDataDTO->identification->id_number,
            'address' => $userDataDTO->address->address_line ?: null,
            'birth_date' => Carbon::make($userDataDTO->personal_info->date_of_birth)?->format('Y-m-d'),
            'birth_place' => $userDataDTO->personal_info->birth_place,
            'photo_selfie' => $this->normalizePhotoSelfie($userDataDTO->documents?->photo_selfie),
        ];
    }

    private function validateData(array $data): void
    {
        $requiredFields = [
            'rq_uuid',
            'rq_datetime',
            'sender_id',
            'user_id',
            'email',
            'phone_number',
            'organization_id',
            'nama_lengkap',
            'nomor_identitas',
            'birth_date',
            'birth_place',
            'photo_selfie',
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, "'{$field}' field is required");
            }
        }
    }

    private function normalizePhotoSelfie(?string $photoSelfie): ?string
    {
        $photoSelfie = trim((string) $photoSelfie);
        if ($photoSelfie === '') {
            return null;
        }

        if (str_starts_with($photoSelfie, 'data:image/')) {
            return $photoSelfie;
        }

        return 'data:image/jpeg;base64,' . $photoSelfie;
    }

    private function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        $phoneNumber = trim((string) $phoneNumber);
        if ($phoneNumber === '') {
            return null;
        }

        if (str_starts_with($phoneNumber, '+62')) {
            return '0' . substr($phoneNumber, 3);
        }

        if (str_starts_with($phoneNumber, '62')) {
            return '0' . substr($phoneNumber, 2);
        }

        return $phoneNumber;
    }

    private function generateSignature(array $data): string
    {
        $signatureKey = (string) config('sijitu.signature_key');
        $format = sprintf(
            '##%s##%s##%s##%s##%s##%s##',
            $data['rq_uuid'],
            $data['sender_id'],
            $data['user_id'],
            $data['nomor_identitas'],
            self::SERVICE_NAME,
            $signatureKey
        );

        return hash('sha256', strtoupper($format));
    }

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

    private function ensureConfigured(): void
    {
        $requiredConfig = [
            'sijitu.url',
            'sijitu.authorization',
            'sijitu.username',
            'sijitu.password',
            'sijitu.sender_id',
            'sijitu.user_id',
            'sijitu.organization_id',
            'sijitu.signature_key',
        ];

        foreach ($requiredConfig as $key) {
            if (blank(config($key))) {
                throw new \RuntimeException("Sijitu configuration missing: {$key}");
            }
        }
    }

    private function isFullyVerified(array $response): bool
    {
        $verificationFields = [
            'verification_name',
            'verification_birthdate',
            'verification_nik',
            'rate_selfie_photo',
            'rate_liveness',
        ];

        foreach ($verificationFields as $field) {
            if (! $this->isVerifiedValue($response[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function getFailedChecks(array $response): array
    {
        $labelMap = [
            'verification_name' => 'name',
            'verification_birthdate' => 'birthdate',
            'verification_nik' => 'nik',
            'rate_selfie_photo' => 'selfie_photo',
            'rate_liveness' => 'liveness',
        ];

        $failedChecks = [];
        foreach ($labelMap as $field => $label) {
            if (! $this->isVerifiedValue($response[$field] ?? null)) {
                $failedChecks[] = $label;
            }
        }

        return $failedChecks;
    }

    private function isVerifiedValue(mixed $value): bool
    {
        return strtolower(trim((string) $value)) === 'verified';
    }
}
