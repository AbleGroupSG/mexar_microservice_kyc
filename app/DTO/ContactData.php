<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class ContactData extends Data
{
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,
    ) {}
}
