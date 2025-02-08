<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $guarded = ['id'];

    public static function saveRequest(array $data, mixed $response, string $provider): void
    {
        if (is_null($response)) {
            $responseToStore = null;
        } elseif (is_string($response)) {
            json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                $responseToStore = $response;
            } else {
                $responseToStore = json_encode(['response' => $response]);
            }
        } elseif (is_array($response)) {
            $responseToStore = json_encode($response);
        } else {
            $responseToStore = $response;
        }
        self::query()->create([
            'provider' => $provider,
            'payload' => json_encode($data),
            'response' => $responseToStore,
        ]);
    }
}
