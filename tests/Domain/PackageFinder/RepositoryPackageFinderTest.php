<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackageFinder;

use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\PackageFinder\RepositoryPackageFinder;
use App\Domain\Packaging;
use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Product;
use App\Domain\Products;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RepositoryPackageFinderTest extends TestCase
{
    private PackagingAssignmentRepository & MockObject $assignmentRepository;
    private RepositoryPackageFinder $packageFinder;

    protected function setUp(): void
    {
        $this->assignmentRepository = $this->createMock(PackagingAssignmentRepository::class);
        $this->packageFinder = new RepositoryPackageFinder($this->assignmentRepository);
    }

    public function testFound(): void
    {
        $products = new Products(
            new Product(width: 300, height: 100, length: 200, weight: 400),
        );
        $normalizedProducts = new Products(
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );
        $packaging = new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findByProducts')
            ->with($normalizedProducts)
            ->willReturn(new PackagingAssignment($normalizedProducts, $packaging));

        self::assertEquals(
            PackageFinderResult::createHit($packaging, RepositoryPackageFinder::class),
            $this->packageFinder->findPackage($products),
        );
    }

    public function testNotFound(): void
    {
        $products = new Products(
            new Product(width: 300, height: 100, length: 200, weight: 400),
            new Product(width: 120, height: 80, length: 100, weight: 200),
        );
        $normalizedProducts = new Products(
            new Product(width: 80, height: 100, length: 120, weight: 200),
            new Product(width: 100, height: 200, length: 300, weight: 400),
        );

        $this->assignmentRepository
            ->expects(self::once())
            ->method('findByProducts')
            ->with($normalizedProducts)
            ->willReturn(null);

        self::assertEquals(
            PackageFinderResult::createMiss(RepositoryPackageFinder::class),
            $this->packageFinder->findPackage($products),
        );
    }
}
