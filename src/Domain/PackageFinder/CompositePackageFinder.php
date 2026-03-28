<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\Products;

final readonly class CompositePackageFinder implements PackageFinder
{
    /**
     * @var list<PackageFinder>
     */
    private array $packageFinders;

    public function __construct(PackageFinder ...$packageFinders)
    {
        $this->packageFinders = $packageFinders;
    }

    public function findPackage(Products $products): PackageFinderResult
    {
        $result = null;

        foreach ($this->packageFinders as $packageFinder) {
            $result = $packageFinder->findPackage($products);
            if ($result->status === PackageFinderResult::HIT) {
                return $result;
            }
        }

        return $result ?? PackageFinderResult::createMiss(self::class);
    }
}
