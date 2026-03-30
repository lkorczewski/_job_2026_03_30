<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\TooManyProducts;

interface PackagingAssignmentRepository
{
    /** @throws TooManyProducts */
    public function save(PackagingAssignment $packagingAssignment): void;

    /** @throws TooManyProducts */
    public function findByProducts(Products $products): ?PackagingAssignment;
}
