<?php

namespace App\Services\KYC;

use App\DTO\UserDataDTO;

interface KYCServiceInterface
{
    public function screen(UserDataDTO $userDataDTO): array;

}
