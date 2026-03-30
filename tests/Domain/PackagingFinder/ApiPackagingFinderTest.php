<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackagingFinder;

use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\PackagingFinder\ApiPackagingFinder;
use App\Domain\PackagingFinder\PackagingFinderResult;
use App\Domain\Product;
use App\Domain\Products;
use App\Infrastructure\Doctrine\DoctrinePackagingsRepository;
use App\Infrastructure\Janedbal\JanedbalApiConnector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiPackagingFinderTest extends TestCase
{
    private DoctrinePackagingsRepository&MockObject $packagingsRepository;
    private JanedbalApiConnector&MockObject $connector;
    private ApiPackagingFinder $packagingFinder;

    protected function setUp(): void
    {
        $this->packagingsRepository = $this->createMock(DoctrinePackagingsRepository::class);
        $this->connector = $this->createMock(JanedbalApiConnector::class);
        $this->packagingFinder = new ApiPackagingFinder($this->packagingsRepository, $this->connector);
    }

    public function testItReturnsHitWithPackagingFromConnector(): void
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
            PackagingFinderResult::createHit($packaging, ApiPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }

    public function testItReturnsHitWithNullPackagingWhenConnectorReturnsNull(): void
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
            PackagingFinderResult::createHit(null, ApiPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }

    public function testItReturnsMissWhenConnectorThrowsRuntimeException(): void
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
            PackagingFinderResult::createMiss(ApiPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }
}
