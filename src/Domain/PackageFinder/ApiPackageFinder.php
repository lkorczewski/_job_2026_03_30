<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\ApiConnector;
use App\Domain\Exception\TooManyProducts;
use App\Domain\PackagingsRepository;
use App\Domain\Products;
use RuntimeException;

final readonly class ApiPackageFinder implements PackageFinder
{
    public function __construct(
        private PackagingsRepository $packagingsRepository,
        private ApiConnector $connector,
    ) {
    }

    /** @throws TooManyProducts */
    public function findPackage(Products $products): PackageFinderResult
    {
        $packagings = $this->packagingsRepository->findAll();

        try {
            $packaging = $this->connector->postPackagingRequest($packagings, $products);
        } catch (RuntimeException) {
            return PackageFinderResult::createMiss(self::class);
        }

        return PackageFinderResult::createHit($packaging, self::class);
    }
}
