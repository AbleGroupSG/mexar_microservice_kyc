<?php

namespace App\Enums;

enum KycServiceTypeEnum: string
{
    case REGTANK = "regtank";

    case GLAIR_AI = "glair";

    case TEST = "test";

    public static function getValues(): array
    {
        return [
            self::REGTANK,
            self::GLAIR_AI,
            self::TEST,
        ];
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
