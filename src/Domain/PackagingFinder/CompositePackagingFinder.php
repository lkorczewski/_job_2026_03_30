<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\Products;

final readonly class CompositePackagingFinder implements PackagingFinder
{
    /**
     * @var list<PackagingFinder>
     */
    private array $packagingFinders;

    public function __construct(PackagingFinder ...$packagingFinders)
    {
        $this->packagingFinders = $packagingFinders;
    }

    public function findPackage(Products $products): PackagingFinderResult
    {
        $result = null;

        foreach ($this->packagingFinders as $packagingFinder) {
            $result = $packagingFinder->findPackage($products);
            if ($result->status === PackagingFinderResult::HIT) {
                return $result;
            }
        }

        return $result ?? PackagingFinderResult::createMiss(self::class);
    }
}
