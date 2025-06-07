<?php

namespace App\Models;

use App\Enums\WebhookTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $platform
 * @property WebhookTypeEnum $type
 * @property array $payload
 */
class WebhookLog extends Model
{
    use HasFactory;
    const REGTANK = 'regtank';

    protected $fillable = [
        'platform',
        'type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'type' => WebhookTypeEnum::class,
    ];

    public static function saveRequest(string $platform, WebhookTypeEnum $type, array $data): void
    {
        self::query()->create([
            'platform' => $platform,
            'type' => $type,
            'payload' => json_encode($data),
        ]);
    }
}
