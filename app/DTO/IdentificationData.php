<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class IdentificationData extends Data
{
    public function __construct(
        public string $id_type,
        public string $id_number,
        public string $issuing_country,
        public ?string $issue_date,
        public ?string $expiry_date,
    ) {}
}
