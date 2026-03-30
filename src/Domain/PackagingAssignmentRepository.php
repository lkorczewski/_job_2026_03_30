<?php

declare(strict_types=1);

namespace App\Domain;

interface PackagingAssignmentRepository
{
    public function save(PackagingAssignment $packagingAssignment): void;

    public function findByProducts(Products $products): ?PackagingAssignment;
}
