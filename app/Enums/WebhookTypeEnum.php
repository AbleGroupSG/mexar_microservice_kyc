<?php

namespace App\Enums;

enum WebhookTypeEnum: string
{
    case KYC = 'kyc';
    case LIVENESS = 'liveness';
    case DJKYB = 'djkyb';
    case DJKYC = 'djkyc';

    public static function fromString(string $type): self
    {
        return match ($type) {
            'kyc' => self::KYC,
            'liveness' => self::LIVENESS,
            'djkyb' => self::DJKYB,
            'djkyc' => self::DJKYC,
            default => throw new \InvalidArgumentException("Invalid webhook type: $type"),
        };
    }
}
