<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case USER = "user";

    case ADMIN = "admin";

    public static function getValues(): array
    {
        return [
            self::USER,
            self::ADMIN,
        ];
    }

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
