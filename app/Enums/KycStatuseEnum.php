<?php

namespace App\Enums;

enum KycStatuseEnum: string
{
    case PENDING = "pending";
    case REJECTED = "rejected";
    case APPROVED = "approved";
    case ERROR = "error";

    public static function getValues(): array
    {
        return [
            self::PENDING,
            self::REJECTED,
            self::APPROVED,
            self::ERROR,
        ];
    }
}
