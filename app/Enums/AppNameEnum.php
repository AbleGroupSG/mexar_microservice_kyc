<?php

namespace App\Enums;

enum AppNameEnum: string
{
    case MEXAR = "MEXAR";
    case E_FORM = "E-FORM";

    public static function getValues(): array
    {
        return [
            self::MEXAR,
            self::E_FORM,
        ];
    }
}
