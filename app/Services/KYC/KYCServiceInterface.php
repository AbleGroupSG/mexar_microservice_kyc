<?php

namespace App\Services\KYC;

use App\DTO\UserDataDTO;
use App\Models\User;

interface KYCServiceInterface
{
    public function screen(UserDataDTO $userDataDTO, User $user): array;

}
