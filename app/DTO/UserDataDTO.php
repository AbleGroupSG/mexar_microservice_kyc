<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class UserDataDTO extends Data
{
    public function __construct(
        public string $uuid,
        public PersonalInfoData $personal_info,
        public IdentificationData $identification,
        public AddressData $address,
        public MetaData $meta,
        public ?ContactData $contact = null,
        public ?DocumentsData $documents = null,
    ) {}
}
