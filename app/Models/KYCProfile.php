<?php

namespace App\Models;

use App\Enums\KycStatuseEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string id
 * @property string profile_data
 * @property int user_id
 * @property int|null user_api_key_id
 * @property string provider_reference_id
 * @property string provider_response_data
 * @property string provider
 * @property KycStatuseEnum|null status
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 * @property User $user
 * @property UserApiKey|null $apiKey
 */
class KYCProfile extends Model
{
    use HasFactory;
    protected $table = 'kyc_profiles';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'profile_data',
        'user_id',
        'user_api_key_id',
        'provider_reference_id',
        'provider_response_data',
        'provider',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'profile_data' => 'array',
        'status' => KycStatuseEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(UserApiKey::class, 'user_api_key_id');
    }
}
