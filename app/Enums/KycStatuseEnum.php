<?php

namespace App\Enums;

enum KycStatuseEnum: string
{
    case PENDING = "pending";
    case REJECTED = "rejected";
    case APPROVED = "approved";

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::REJECTED,
            self::APPROVED,
        ];
    }
}
