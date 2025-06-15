<?php

namespace App\Services\IndividualOnboarding;

use App\Services\KYC\Regtank\RegtankAuth;
use Illuminate\Support\Facades\Http;

class RegtankIndividualOnboardingService
{
    public function createProfile(array $data): string
    {
        $accessToken = RegtankAuth::getToken();
        $url = config('e-form.regtank.specific_server_url');
        return Http::withToken($accessToken)
            ->post("$url/v3/onboarding/indv/request", $data)
            ->body();
    }

    public function getDetails(string $requestId)
    {
        $accessToken = RegtankAuth::getToken();
        $url = config('e-form.regtank.specific_server_url');
        return Http::withToken($accessToken)
            ->get("$url/v3/onboarding/indv/query?requestId=". $requestId)
            ->body();
    }

}
