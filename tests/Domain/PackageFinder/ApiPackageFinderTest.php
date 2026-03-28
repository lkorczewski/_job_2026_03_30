<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackageFinder;

use App\Domain\ApiConnector;
use App\Domain\PackageFinder\ApiPackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\PackagingsRepository;
use App\Domain\Product;
use App\Domain\Products;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiPackageFinderTest extends TestCase
{
    private PackagingsRepository & MockObject $packagingsRepository;
    private ApiConnector & MockObject $connector;
    private ApiPackageFinder $packageFinder;

    protected function setUp(): void
    {
        $this->packagingsRepository = $this->createMock(PackagingsRepository::class);
        $this->connector = $this->createMock(ApiConnector::class);
        $this->packageFinder = new ApiPackageFinder($this->packagingsRepository, $this->connector);
    }

    public function testFound(): void
    {
        $products = new Products(
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $packagings = new Packagings(
            new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500),
            new Packaging(id: 2, width: 120, height: 220, length: 320, maxWeight: 600),
        );
        $packaging = new Packaging(id: 2, width: 120, height: 220, length: 320, maxWeight: 600);

        $this->packagingsRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($packagings);

        $this->connector
            ->expects(self::once())
            ->method('postPackagingRequest')
            ->with($packagings, $products)
            ->willReturn($packaging);

        self::assertEquals(
            PackageFinderResult::createHit($packaging, ApiPackageFinder::class),
            $this->packageFinder->findPackage($products),
        );
    }

    public function testNotFound(): void
    {
        $products = new Products(
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $packagings = new Packagings(
            new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500),
        );

        $this->packagingsRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($packagings);

        $this->connector
            ->expects(self::once())
            ->method('postPackagingRequest')
            ->with($packagings, $products)
            ->willReturn(null);

        self::assertEquals(
            PackageFinderResult::createHit(null, ApiPackageFinder::class),
            $this->packageFinder->findPackage($products),
        );
    }

    public function testConnectorException(): void
    {
        $products = new Products(
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $packagings = new Packagings(
            new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500),
        );

        $this->packagingsRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($packagings);

        $this->connector
            ->expects(self::once())
            ->method('postPackagingRequest')
            ->with($packagings, $products)
            ->willThrowException(new RuntimeException('Upstream request failed'));

        self::assertEquals(
            PackageFinderResult::createMiss(ApiPackageFinder::class),
            $this->packageFinder->findPackage($products),
        );
    }
}
