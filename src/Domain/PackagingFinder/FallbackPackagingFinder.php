<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\PackagingsRepository;
use App\Domain\Product;
use App\Domain\Products;

final readonly class FallbackPackagingFinder implements PackagingFinder
{
    public function __construct(private PackagingsRepository $packagingsRepository)
    {
    }

    public function findPackage(Products $products): PackagingFinderResult
    {
        if ($products->count() !== 1) {
            return PackagingFinderResult::createMiss(self::class);
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

        return PackagingFinderResult::createHit($selectedPackaging, self::class);
    }
}
