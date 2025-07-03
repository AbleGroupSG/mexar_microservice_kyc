<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class MetaData extends Data
{
    public function __construct(
        public string $service_provider,
        public string $reference_id,
        public ?string $status = null,
    ) {}
}
