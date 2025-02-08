<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string provider
 * @property string kyc_profile_id
 * @property string payload
 * @property string response
 */
class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';
    protected $fillable = [
        'provider',
        'kyc_profile_id',
        'payload',
        'response',
    ];

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KYCProfile::class, 'kyc_profile_id');
    }

    public static function saveRequest(array $data, mixed $response, string $kycProfileId, string $provider): void
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
            'kyc_profile_id' => $kycProfileId,
            'provider' => $provider,
            'payload' => json_encode($data),
            'response' => $responseToStore,
        ]);
    }
}
