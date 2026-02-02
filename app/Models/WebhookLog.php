<?php

namespace App\Models;

use App\Enums\WebhookTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * @property string $platform
 * @property WebhookTypeEnum $type
 * @property string|null $idempotency_key
 * @property array $payload
 */
class WebhookLog extends Model
{
    use HasFactory;

    const REGTANK = 'regtank';

    protected $fillable = [
        'platform',
        'type',
        'idempotency_key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'type' => WebhookTypeEnum::class,
    ];

    /**
     * Build a standardized idempotency key from webhook identifiers.
     *
     * Format: {platform}:{type}:{requestId}
     */
    public static function buildIdempotencyKey(string $platform, WebhookTypeEnum $type, string $requestId): string
    {
        return "{$platform}:{$type->value}:{$requestId}";
    }

    /**
     * Atomically save a webhook request with idempotency protection.
     *
     * Uses database unique constraint to prevent duplicate processing.
     * Returns the WebhookLog instance if newly created, or null if duplicate.
     */
    public static function saveRequestOnce(
        string $platform,
        WebhookTypeEnum $type,
        string $requestId,
        array $data
    ): ?self {
        $idempotencyKey = self::buildIdempotencyKey($platform, $type, $requestId);

        try {
            return self::query()->create([
                'platform' => $platform,
                'type' => $type,
                'idempotency_key' => $idempotencyKey,
                'payload' => json_encode($data),
            ]);
        } catch (UniqueConstraintViolationException) {
            Log::warning('Duplicate webhook detected, skipping processing', [
                'idempotency_key' => $idempotencyKey,
                'platform' => $platform,
                'type' => $type->value,
                'request_id' => $requestId,
            ]);

            return null;
        }
    }

    public static function saveRequest(string $platform, WebhookTypeEnum $type, array $data): void
    {
        self::query()->create([
            'platform' => $platform,
            'type' => $type,
            'payload' => json_encode($data),
        ]);
    }
}
