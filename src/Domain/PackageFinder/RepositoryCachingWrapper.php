<?php

namespace App\Domain\PackageFinder;

use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Products;

final readonly class RepositoryCachingWrapper implements PackageFinder
{
    public function __construct(
        private PackageFinder $packageFinder,
        private PackagingAssignmentRepository $packagingAssignmentRepository,
    ) {
    }

    public function findPackage(Products $products): PackageFinderResult
    {
        $result = $this->packageFinder->findPackage($products);

        if ($result->status === PackageFinderResult::HIT) {
            $this->packagingAssignmentRepository->save(
                new PackagingAssignment($products->normalize(), $result->packaging)
            );
        }

        return $result;
    }
}
