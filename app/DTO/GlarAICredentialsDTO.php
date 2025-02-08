<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class GlarAICredentialsDTO extends Data
{
    public function __construct(
        public string $url,
        public string $username,
        public string $password,
        public string $apiKey,
    ) {}
}
