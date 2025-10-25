<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public ?string $street,
        public string $city,
        public ?string $state,
        public ?string $postal_code,
        public string $country,
        public string $address_line = '',
    ) {}
}
