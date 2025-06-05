<?php

namespace App\Services\KYC\GlairAI;

class KYCResponse
{
    private array $religion = [];
    private array $address = [];
    private array $validUntil = [];
    private array $bloodType = [];
    private array $gender = [];
    private array $district = [];
    private array $subdistrictVillage = [];
    private array $nationality = [];
    private array $cityRegency = [];
    private array $name = [];
    private array $nik = [];
    private array $occupation = [];
    private array $province = [];
    private array $neighborhoodAssociation = [];
    private array $maritalStatus = [];
    private array $birthDate = [];
    private array $birthPlace = [];
    public function __construct(array $read)
    {
        $this->mapResponse($read);
    }

    public static function make(array $read): KYCResponse
    {
        return new self($read);
    }

    private function mapResponse(array $read): void
    {
        $this->religion = $read['agama'] ?? null;
        $this->address = $read['alamat'] ?? null;
        $this->validUntil = $read['berlakuHingga'] ?? null;
        $this->bloodType = $read['golonganDarah'] ?? null;
        $this->gender = $read['jenisKelamin'] ?? null;
        $this->district = $read['kecamatan'] ?? null;
        $this->subdistrictVillage = $read['kelurahanDesa'] ?? null;
        $this->nationality = $read['kewarganegaraan'] ?? null;
        $this->cityRegency = $read['kotaKabupaten'] ?? null;
        $this->name = $read['nama'] ?? null;
        $this->nik = $read['nik'] ?? null;
        $this->occupation = $read['pekerjaan'] ?? null;
        $this->province = $read['provinsi'] ?? null;
        $this->neighborhoodAssociation = $read['rtRw'] ?? null;
        $this->maritalStatus = $read['statusPerkawinan'] ?? null;
        $this->birthDate = $read['tanggalLahir'] ?? null;
        $this->birthPlace = $read['tempatLahir'] ?? null;
    }

    /**
     * Get the religion data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the religion.
     *
     * @return array{confidence?: int, value?: string} The religion data.
     */
    public function getReligion(): array
    {
        return $this->religion ?? [];
    }

    /**
     * Get the address data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the religion.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getAddress(): array
    {
        return $this->address ?? [];
    }

    /**
     * Get the ValidUntil data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the ValidUntil.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getValidUntil(): array
    {
        return $this->validUntil ?? [];
    }

    /**
     * Get the BloodType data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the BloodType.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getBloodType(): array
    {
        return $this->bloodType ?? [];
    }

    /**
     * Get the gender data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the gender.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getGender(): array
    {
        return $this->gender ?? [];
    }

    /**
     * Get the District data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the District.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getDistrict(): array
    {
        return $this->district ?? [];
    }

    /**
     * Get the SubdistrictVillage data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the SubdistrictVillage.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getSubdistrictVillage():array
    {
        return $this->subdistrictVillage ?? [];
    }

    /**
     * Get the Nationality data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the Nationality.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getNationality():array
    {
        return $this->nationality ?? [];
    }

    /**
     * Get the CityRegency data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the CityRegency.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getCityRegency():array
    {
        return $this->cityRegency ?? [];
    }

    /**
     * Get the Name data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the Name.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getName():array
    {
        return $this->name ?? [];
    }

    /**
     * Get the Nik (id) data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the Nik (id).
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getNik():array
    {
        return $this->nik ?? [];
    }

    /**
     * Get the Occupation data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the Occupation.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getOccupation():array
    {
        return $this->occupation ?? [];
    }

    /**
     * Get the Province data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the Province.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getProvince():array
    {
        return $this->province ?? [];
    }

    /**
     * Get the NeighborhoodAssociation data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the NeighborhoodAssociation.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getNeighborhoodAssociation():array
    {
        return $this->neighborhoodAssociation ?? [];
    }

    /**
     * Get the MaritalStatus data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the MaritalStatus.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getMaritalStatus():array
    {
        return $this->maritalStatus ?? [];
    }

    /**
     * Get the BirthDate data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the BirthDate.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getBirthDate():array
    {
        return $this->birthDate ?? [];
    }

    /**
     * Get the BirthPlace data.
     *
     * The returned array can either be empty or contain the following fields:
     * - "confidence" (int): The confidence level.
     * - "value" (string): The value of the BirthPlace.
     *
     * @return array{confidence?: int, value?: string} The address data.
     */
    public function getBirthPlace():array
    {
        return $this->birthPlace ?? [];
    }


    /**
     * Convert the object to an array.
     *
     * @return array The array representation of the object.
     */
    public function toArray(): array
    {
        return [
            'religion' => $this->religion,
            'address' => $this->address,
            'validUntil' => $this->validUntil,
            'bloodType' => $this->bloodType,
            'gender' => $this->gender,
            'district' => $this->district,
            'subdistrictVillage' => $this->subdistrictVillage,
            'nationality' => $this->nationality,
            'cityRegency' => $this->cityRegency,
            'name' => $this->name,
            'nik' => $this->nik,
            'occupation' => $this->occupation,
            'province' => $this->province,
            'neighborhoodAssociation' => $this->neighborhoodAssociation,
            'maritalStatus' => $this->maritalStatus,
            'birthDate' => $this->birthDate,
            'birthPlace' => $this->birthPlace,
        ];
    }
}
