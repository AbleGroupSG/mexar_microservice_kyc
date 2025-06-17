<?php

namespace App\Enums;

enum KycServiceTypeEnum: string
{
    case REGTANK = "REGTANK";
    case GLAIR_AI = "GLAIR_AI";
    case TEST = "TEST";

    public static function getValues(): array
    {
        return [
            self::REGTANK,
            self::GLAIR_AI,
            self::TEST,
        ];
    }
}
