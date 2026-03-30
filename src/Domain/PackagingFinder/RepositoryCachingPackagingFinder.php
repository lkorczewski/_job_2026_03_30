<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Products;

final readonly class RepositoryCachingPackagingFinder implements PackagingFinder
{
    public function __construct(
        private PackagingFinder $packagingFinder,
        private PackagingAssignmentRepository $packagingAssignmentRepository,
    ) {
    }

    public function findPackage(Products $products): PackagingFinderResult
    {
        $result = $this->packagingFinder->findPackage($products);

        if ($result->status === PackagingFinderResult::HIT) {
            $this->packagingAssignmentRepository->save(
                new PackagingAssignment($products->normalize(), $result->packaging)
            );
        }

        return $result;
    }
}
