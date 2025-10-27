<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $api_key
 * @property string|null $signature_key
 * @property string|null $webhook_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property User $user
 */
class UserApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users_api_keys';

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'signature_key',
        'webhook_url',
    ];

    protected $hidden = [
        'api_key',
        'signature_key',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the KYC profiles that used this API key.
     */
    public function kycProfiles(): HasMany
    {
        return $this->hasMany(KYCProfile::class, 'user_api_key_id');
    }
}
