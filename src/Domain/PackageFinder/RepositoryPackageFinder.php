<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\PackagingAssignmentRepository;
use App\Domain\Products;

final readonly class RepositoryPackageFinder implements PackageFinder
{
    public function __construct(
        private PackagingAssignmentRepository $assignmentRepository,
    ) {
    }

    public function findPackage(Products $products): PackageFinderResult
    {
        $packagingAssignment = $this->assignmentRepository->findByProducts($products->normalize());

        return $packagingAssignment
            ? PackageFinderResult::createHit($packagingAssignment->packaging, self::class)
            : PackageFinderResult::createMiss(self::class);
    }
}
