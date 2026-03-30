<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackageFinder;

use App\Domain\PackageFinder\PackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\PackageFinder\RepositoryCachingWrapper;
use App\Domain\Packaging;
use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Product;
use App\Domain\Products;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RepositoryCachingWrapperTest extends TestCase
{
    private PackageFinder & MockObject $packageFinder;
    private PackagingAssignmentRepository & MockObject $packagingAssignmentRepository;
    private RepositoryCachingWrapper $wrapper;

    protected function setUp(): void
    {
        $this->packageFinder = $this->createMock(PackageFinder::class);
        $this->packagingAssignmentRepository = $this->createMock(PackagingAssignmentRepository::class);
        $this->wrapper = new RepositoryCachingWrapper($this->packageFinder, $this->packagingAssignmentRepository);
    }

    public function testSavingWhenHit(): void
    {
        $products = new Products(
            new Product(width: 300, height: 100, length: 200, weight: 400),
            new Product(width: 120, height: 80, length: 100, weight: 200),
        );
        $normalizedProducts = new Products(
            new Product(width: 80, height: 100, length: 120, weight: 200),
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $packaging = new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500);
        $result = PackageFinderResult::createHit($packaging, 'wrapped-finder');

        $this->packageFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($result);

        $this->packagingAssignmentRepository
            ->expects(self::once())
            ->method('save')
            ->with(new PackagingAssignment($normalizedProducts, $packaging));

        self::assertSame($result, $this->wrapper->findPackage($products));
    }

    public function testSavingWhenHitWithoutPackaging(): void
    {
        $products = new Products(
            new Product(width: 300, height: 100, length: 200, weight: 400),
        );
        $normalizedProducts = new Products(
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $result = PackageFinderResult::createHit(null, 'wrapped-finder');

        $this->packageFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($result);

        $this->packagingAssignmentRepository
            ->expects(self::once())
            ->method('save')
            ->with(new PackagingAssignment($normalizedProducts, null));

        self::assertSame($result, $this->wrapper->findPackage($products));
    }

    public function testNotSavingWhenMiss(): void
    {
        $products = new Products(
            new Product(width: 300, height: 100, length: 200, weight: 400),
        );
        $result = PackageFinderResult::createMiss('wrapped-finder');

        $this->packageFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($result);

        $this->packagingAssignmentRepository
            ->expects(self::never())
            ->method('save');

        self::assertSame($result, $this->wrapper->findPackage($products));
    }
}
