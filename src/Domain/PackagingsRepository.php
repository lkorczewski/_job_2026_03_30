<?php

declare(strict_types=1);

namespace App\Domain;

interface PackagingsRepository
{
    public function findAll(): Packagings;
}
