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
 * @property string|null review_notes
 * @property int|null reviewed_by
 * @property Carbon|null reviewed_at
 * @property KycStatuseEnum|null provider_status
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 * @property User $user
 * @property UserApiKey|null $apiKey
 * @property User|null $reviewer
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
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'provider_status',
    ];

    protected $casts = [
        'id' => 'string',
        'profile_data' => 'array',
        'provider_response_data' => 'array',
        'status' => KycStatuseEnum::class,
        'reviewed_at' => 'datetime',
        'provider_status' => KycStatuseEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(UserApiKey::class, 'user_api_key_id');
    }

    /**
     * Get the user who reviewed this profile.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if this profile is awaiting manual review.
     */
    public function isAwaitingReview(): bool
    {
        return $this->status?->isAwaitingReview() ?? false;
    }

    /**
     * Check if this profile requires manual review based on the API key setting.
     */
    public function needsManualReview(): bool
    {
        return $this->apiKey?->need_manual_review ?? false;
    }
}
