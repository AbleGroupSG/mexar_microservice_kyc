<?php

namespace App\DTO;

use Spatie\LaravelData\Data;

class UserDataDTO extends Data
{
    public function __construct(
        public PersonalInfoData $personal_info,
        public IdentificationData $identification,
        public AddressData $address,
        public ContactData $contact,
        public DocumentsData $documents,
        public MetaData $meta,
    ) {}
}

class PersonalInfoData extends Data
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $gender,
        public string $date_of_birth,
        public string $nationality,
    ) {}
}

class IdentificationData extends Data
{
    public function __construct(
        public string $id_type,
        public string $id_number,
        public string $issuing_country,
        public string $issue_date,
        public string $expiry_date,
    ) {}
}

class AddressData extends Data
{
    public function __construct(
        public string $street,
        public string $city,
        public string $state,
        public string $postal_code,
        public string $country,
    ) {}
}

class ContactData extends Data
{
    public function __construct(
        public string $email,
        public string $phone,
    ) {}
}

class DocumentsData extends Data
{
    public function __construct(
        public string $id_front,
        public string $id_back,
        public string $passport,
        public string $utility_bill,
    ) {}
}

class MetaData extends Data
{
    public function __construct(
        public string $service_provider,
        public string $reference_id,
    ) {}
}
