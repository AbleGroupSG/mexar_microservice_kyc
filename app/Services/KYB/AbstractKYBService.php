<?php

namespace App\Services\KYB;

abstract class AbstractKYBService
{
    abstract public function checkStatus(string $identity): mixed;

    abstract public function createProfile(array $data): mixed;
}
