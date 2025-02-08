<?php

namespace App\Services\KYC;

use App\DTO\UserDataDTO;

class GlairAIService implements KYCServiceInterface
{
    public function __construct(UserDataDTO $data)
    {
    }

    public function screen(): array
    {

        return [];
    }
}
