<?php

namespace App\Models;

use App\Enums\KycStatuseEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 * @property string profile_data
 * @property string provider
 * @property KycStatuseEnum|null status
 */
class KYCProfile extends Model
{
    protected $table = 'kyc_profiles';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'profile_data',
        'provider',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
        'profile_data' => 'array',
        'status' => KycStatuseEnum::class,
    ];
}
