<?php

namespace App\DTO;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class PersonalInfoData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?string $gender,
        public ?string $date_of_birth,
        public string $nationality,
    ) {}
}
