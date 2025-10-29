<?php

namespace App\Models;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $api_key
 * @property UserTypeEnum $user_type
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_key',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserTypeEnum::class,
        ];
    }

    /**
     * Get the API keys for the user.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(UserApiKey::class);
    }

    /**
     * Get the KYC profiles for the user.
     */
    public function kycProfiles(): HasMany
    {
        return $this->hasMany(KYCProfile::class);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->user_type === UserTypeEnum::ADMIN;
    }

    /**
     * Check if the user is a standard user.
     */
    public function isUser(): bool
    {
        return $this->user_type === UserTypeEnum::USER;
    }
}
