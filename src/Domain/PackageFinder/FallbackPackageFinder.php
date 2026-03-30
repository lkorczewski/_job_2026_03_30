<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\PackagingsRepository;
use App\Domain\Product;
use App\Domain\Products;

final readonly class FallbackPackageFinder implements PackageFinder
{
    public function __construct(private PackagingsRepository $packagingsRepository)
    {
    }

    public function findPackage(Products $products): PackageFinderResult
    {
        if ($products->count() !== 1) {
            return PackageFinderResult::createMiss(self::class);
        }

        /** @var Product $normalizedProduct */
        $normalizedProduct = iterator_to_array($products)[0]->normalize();

        $packagings = $this->packagingsRepository->findAll();

        $selectedPackaging = null;
        foreach ($packagings as $packaging) {
            $normalizedPackaging = $packaging->normalize();
            if (
                $normalizedProduct->width <= $normalizedPackaging->width
                && $normalizedProduct->height <= $normalizedPackaging->height
                && $normalizedProduct->length <= $normalizedPackaging->length
                && $normalizedProduct->weight <= $normalizedPackaging->maxWeight
            ) {
                if (
                    $selectedPackaging === null
                    || $packaging->getVolume() < $selectedPackaging->getVolume()
                ) {
                    $selectedPackaging = $packaging;
                }
            }
        }

        return PackageFinderResult::createHit($selectedPackaging, self::class);
    }
}
