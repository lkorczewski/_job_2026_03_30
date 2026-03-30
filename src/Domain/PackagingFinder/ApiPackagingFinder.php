<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\ApiConnector;
use App\Domain\PackagingsRepository;
use App\Domain\Products;
use RuntimeException;

final readonly class ApiPackagingFinder implements PackagingFinder
{
    public function __construct(
        private PackagingsRepository $packagingsRepository,
        private ApiConnector $connector,
    ) {
    }

    public function findPackage(Products $products): PackagingFinderResult
    {
        $packagings = $this->packagingsRepository->findAll();

        try {
            $packaging = $this->connector->postPackagingRequest($packagings, $products);
        } catch (RuntimeException) {
            return PackagingFinderResult::createMiss(self::class);
        }

        return PackagingFinderResult::createHit($packaging, self::class);
    }
}
