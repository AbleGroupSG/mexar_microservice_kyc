<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string provider
 * @property string request_uuid
 * @property string payload
 * @property string response
 */
class ApiRequestLog extends Model
{
    use HasFactory;

    private const TEXT_LIMIT = 65535;

    private const BASE64_PREVIEW_LENGTH = 64;

    protected $table = 'api_request_logs';
    protected $fillable = [
        'provider',
        'request_uuid',
        'payload',
        'response',
    ];

    public function kycProfile(): BelongsTo
    {
        return $this->belongsTo(KYCProfile::class, 'request_uuid');
    }

    public static function saveRequest(array $data, mixed $response, string $request_uuid, string $provider): void
    {
        $sanitizedPayload = self::sanitizeForStorage($data);
        $sanitizedResponse = self::sanitizeForStorage($response);

        $payloadJson = self::encodeForStorage(self::truncateLargeBase64($sanitizedPayload));
        $responseJson = self::encodeForStorage(self::truncateLargeBase64($sanitizedResponse));

        self::query()->create([
            'request_uuid' => $request_uuid,
            'provider' => $provider,
            'payload' => self::truncateText($payloadJson),
            'response' => self::truncateText($responseJson),
        ]);
    }

    private static function sanitizeForStorage(mixed $value): mixed
    {
        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $decoded = json_decode($value, true);
                return self::truncateLargeBase64($decoded);
            }

            return self::truncateLargeBase64($value);
        }

        if (is_array($value)) {
            return self::truncateLargeBase64($value);
        }

        return self::truncateLargeBase64((string) $value);
    }

    private static function truncateLargeBase64(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::truncateLargeBase64($item);
            }

            return $sanitized;
        }

        if (! is_string($value)) {
            return $value;
        }

        if (! str_starts_with($value, 'data:image/')) {
            return self::truncateText($value);
        }

        $parts = explode(',', $value, 2);
        if (count($parts) !== 2) {
            return self::truncateText($value);
        }

        [$meta, $base64] = $parts;
        $preview = substr($base64, 0, self::BASE64_PREVIEW_LENGTH);

        return sprintf(
            '%s,%s...[truncated %d chars]',
            $meta,
            $preview,
            max(strlen($base64) - self::BASE64_PREVIEW_LENGTH, 0)
        );
    }

    private static function encodeForStorage(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function truncateText(?string $text, int $limit = self::TEXT_LIMIT): ?string
    {
        if ($text === null || strlen($text) <= $limit) {
            return $text;
        }

        $suffix = sprintf('...[truncated %d chars]', strlen($text) - $limit);
        $sliceLength = max($limit - strlen($suffix), 0);

        return substr($text, 0, $sliceLength) . $suffix;
    }
}
