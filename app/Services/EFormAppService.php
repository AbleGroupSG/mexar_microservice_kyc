<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EFormAppService
{
    public function send(array $data): void
    {
        $url = config('app.eform.url');
        $response = Http::post("$url/kyc", $data);

        if (!$response->successful()) {
            logger()->error('Failed to send KYC data to E-Form', [
                'status' => $response->status(),
                'response' => $response->json(),
                'data' => $data,
            ]);
        } else {
            logger()->info('KYC data sent to E-Form successfully', ['response' => $response->body()]);
        }
    }
}
