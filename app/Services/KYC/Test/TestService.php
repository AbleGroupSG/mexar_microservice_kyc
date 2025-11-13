<?php

namespace App\Services\KYC\Test;

use App\DTO\UserDataDTO;
use App\Enums\KycStatuseEnum;
use App\Jobs\TestKYCResultJob;
use App\Models\ApiRequestLog;
use App\Models\KYCProfile;
use App\Models\User;
use App\Models\UserApiKey;
use App\Services\KYC\KYCServiceInterface;
use Illuminate\Support\Str;

/**
 * Test KYC Verification Service (Mock Provider)
 *
 * Provides mock KYC verification for development and testing without calling
 * external APIs. Simulates async workflow with controllable outcomes via
 * meta.status parameter. Useful for integration testing, CI/CD, and development
 * environments where real provider calls are not needed.
 */
class TestService implements KYCServiceInterface
{
    /**
     * Submit mock KYC screening request (async processing via background job).
     *
     * Creates a KYC profile with PENDING status and dispatches a background job
     * that simulates verification processing with a random delay (5-10 seconds).
     * The final status can be controlled via meta.status parameter for testing
     * different verification outcomes.
     *
     * Supported test outcomes via meta.status:
     * - 'approved': Simulates successful verification
     * - 'rejected': Simulates failed verification
     * - 'error': Simulates system error
     * - 'unresolved': Simulates manual review required
     * - null/empty: Defaults to 'approved'
     *
     * @param UserDataDTO $userDataDTO User data including personal info and identification
     * @param User $user The authenticated user
     * @param UserApiKey $userApiKey The API key used for this request
     * @return array Array containing 'identity' key with profile UUID
     */
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        // Create profile with PENDING status (async pattern)
        $profile = new KYCProfile;
        $profile->id = $userDataDTO->uuid;
        $profile->profile_data = $userDataDTO->toJson();
        $profile->provider = $userDataDTO->meta->service_provider;
        $profile->user_id = $user->id;
        $profile->user_api_key_id = $userApiKey->id;
        $profile->status = KycStatuseEnum::PENDING;
        $profile->provider_reference_id = Str::random(10);
        $profile->save();

        // Optional: Allow test requests to control desired outcome via meta->status
        $desiredStatus = $userDataDTO->meta->status ?? null;

        // Dispatch async job to process verification
        TestKYCResultJob::dispatch(
            profileId: $profile->id,
            desiredStatus: $desiredStatus,
        )->delay(now()->addSeconds(mt_rand(5, 10)));

        ApiRequestLog::saveRequest(
            ['user_data' => $userDataDTO->toJson()],
            ['status' => 'pending', 'desired_status' => $desiredStatus],
            $userDataDTO->uuid,
            $userDataDTO->meta->service_provider,
        );

        // Return only reference ID (consistent with RegTank and GlairAI)
        return ['identity' => $profile->id];
    }
}
