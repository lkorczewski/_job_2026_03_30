<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\PackagingAssignmentRepository;
use App\Domain\Products;

final readonly class RepositoryPackagingFinder implements PackagingFinder
{
    public function __construct(
        private PackagingAssignmentRepository $assignmentRepository,
    ) {
    }

    public function findPackage(Products $products): PackagingFinderResult
    {
        $packagingAssignment = $this->assignmentRepository->findByProducts($products->normalize());

        return $packagingAssignment
            ? PackagingFinderResult::createHit($packagingAssignment->packaging, self::class)
            : PackagingFinderResult::createMiss(self::class);
    }
}
