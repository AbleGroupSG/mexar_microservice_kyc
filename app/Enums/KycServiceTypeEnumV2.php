<?php

namespace App\Enums;

enum KycServiceTypeEnumV2: string
{
    case KTP = "ktp";
    case PASSPORT = "passport";

    public static function getValues(): array
    {
        return [
            self::KTP,
            self::PASSPORT,
        ];
    }
}
