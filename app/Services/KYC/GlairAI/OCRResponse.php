<?php

namespace App\Services\KYC\GlairAI;

class OCRResponse
{
    private array $data;

    private array $mapped = [
        'religion' => null,
        'address' => null,
        'validUntil' => null,
        'bloodType' => null,
        'gender' => null,
        'district' => null,
        'subdistrictVillage' => null,
        'nationality' => null,
        'cityRegency' => null,
        'name' => null,
        'nik' => null,
        'occupation' => null,
        'province' => null,
        'neighborhoodAssociation' => null,
        'maritalStatus' => null,
        'birthDate' => null,
        'birthPlace' => null,
    ];

    public function __construct(array $read)
    {
        $this->data = $read;
        $this->mapData();
    }

    public static function make(array $read): OCRResponse
    {
        return new self($read);
    }

    private function mapData(): void
    {
        $this->mapped['religion'] = $this->getValue('agama');
        $this->mapped['address'] = $this->getValue('alamat');
        $this->mapped['validUntil'] = $this->getValue('berlakuHingga');
        $this->mapped['bloodType'] = $this->getValue('golonganDarah');
        $this->mapped['gender'] = $this->getValue('jenisKelamin') ?? $this->getValue('sex');
        $this->mapped['district'] = $this->getValue('kecamatan');
        $this->mapped['subdistrictVillage'] = $this->getValue('kelurahanDesa');
        $this->mapped['nationality'] = $this->getValue('kewarganegaraan') ?? $this->getValue('nationality');
        $this->mapped['cityRegency'] = $this->getValue('kotaKabupaten');
        $this->mapped['name'] = $this->getValue('nama') ?? $this->getValue('name');
        $this->mapped['nik'] = $this->getValue('nik') ?? $this->getValue('doc_number');
        $this->mapped['occupation'] = $this->getValue('pekerjaan') ?? $this->getValue('optional_data');
        $this->mapped['province'] = $this->getValue('provinsi') ?? $this->getValue('country');
        $this->mapped['neighborhoodAssociation'] = $this->getValue('rtRw');
        $this->mapped['maritalStatus'] = $this->getValue('statusPerkawinan');
        $this->mapped['birthDate'] = $this->getValue('tanggalLahir') ?? $this->getValue('birth_date');
        $this->mapped['birthPlace'] = $this->getValue('tempatLahir') ?? $this->getValue('surname');
    }

    private function getValue(string $key): ?string
    {
        return $this->data[$key]['value'] ?? null;
    }

    public function toArray(): array
    {
        return $this->mapped;
    }
}
