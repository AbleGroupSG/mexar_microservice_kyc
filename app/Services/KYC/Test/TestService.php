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

class TestService implements KYCServiceInterface
{
    public function screen(UserDataDTO $userDataDTO, User $user, UserApiKey $userApiKey): array
    {
        $status = KycStatuseEnum::from($userDataDTO->meta->status ?? 'approved');
        $profile = new KYCProfile();
        $profile->id = $userDataDTO->uuid;
        $profile->profile_data = $userDataDTO->toJson();
        $profile->provider = $userDataDTO->meta->service_provider;
        $profile->user_id = $user->id;
        $profile->user_api_key_id = $userApiKey->id;
        $profile->status = $status;
        $profile->provider_reference_id = Str::random(10);
        $profile->save();

        TestKYCResultJob::dispatch(
            userDataDTO: $userDataDTO,
            status: $status,
        )->delay(now()->addSeconds(mt_rand(5, 10)));

        ApiRequestLog::saveRequest(
            ['user_data' => $userDataDTO->toJson()],
            ['status' => $status],
            $userDataDTO->uuid,
            $userDataDTO->meta->service_provider,
        );

        return [
            'status' => $status
        ];
    }
}
