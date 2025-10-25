<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class DocumentsData extends Data
{
    public function __construct(
        public string $id_front,
        public string $id_back,
        public string $passport,
        public string $utility_bill,
    ) {}
}
