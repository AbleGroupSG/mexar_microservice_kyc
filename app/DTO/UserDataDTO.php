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
        public ?ContactData $contact,
//        public DocumentsData $documents,
        public MetaData $meta,
    ) {}
}
