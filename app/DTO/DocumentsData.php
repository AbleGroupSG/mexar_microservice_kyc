<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class DocumentsData extends Data
{
    public function __construct(
        public ?string $photo_selfie = null,
    ) {}
}
