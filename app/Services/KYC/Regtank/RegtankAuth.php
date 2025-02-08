<?php

namespace App\Services\KYC\Regtank;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegtankAuth
{
    /**
     * @throws \Throwable
     */
    private static function auth():void
    {
        $url = config('regtank.server_url');
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => config('regtank.id_template'),
            'client_secret' => config('regtank.secret_template'),
        ];
        $queryString = http_build_query($params);
        $maxRetries = 3;
        $retryDelay = 1000;

        $response = retry($maxRetries, function () use ($url, $queryString) {

            $response = Http::connectTimeout(20)->post("$url/oauth/token?$queryString");

            if ($response->successful()) {
                return $response;
            } else {
                match ($response->status()) {
                    400 => Log::error('Bad Request: ' . $response->body()),
                    500 => Log::error('Server Error: ' . $response->body()),
                    default => Log::error('Unexpected Error: ' . $response->body()),
                };

                throw new Exception('Request failed with status ' . $response->status());
            }
        }, $retryDelay);

        if ($response) {
            $data = $response->json();
            Cache::put('access_token', $data['access_token'], now()->addSeconds($data['expires_in']));
        } else {
            // Should it be specific exception to be cought in Service provider or just regular exception?
            throw new Exception('Failed to obtain access token after ' . $maxRetries . ' attempts.');
        }
    }

    /**
     * @throws \Throwable
     */
    public static function getToken(): string
    {
        $accessToken = Cache::get('access_token');
        if(!$accessToken) {
            self::auth();
            $accessToken = Cache::get('access_token');
        }
        return $accessToken;
    }
}
