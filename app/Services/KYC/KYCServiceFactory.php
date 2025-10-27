<?php

namespace App\Services\KYC;

use App\Enums\KycServiceTypeEnum;
use App\Services\KYC\GlairAI\GlairAIService;
use App\Services\KYC\Regtank\RegtankService;
use App\Services\KYC\Test\TestService;

class KYCServiceFactory
{
    /**
     * Create a KYC service instance based on the provider type.
     *
     * @param KycServiceTypeEnum $type The service provider type
     * @return KYCServiceInterface
     */
    public static function make(KycServiceTypeEnum $type): KYCServiceInterface
    {
        return match($type) {
            KycServiceTypeEnum::REGTANK => app(RegtankService::class),
            KycServiceTypeEnum::GLAIR_AI => app(GlairAIService::class),
            KycServiceTypeEnum::TEST => app(TestService::class),
        };
    }
}
