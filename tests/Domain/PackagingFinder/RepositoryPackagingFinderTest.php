<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackagingFinder;

use App\Domain\Packaging;
use App\Domain\PackagingAssignment;
use App\Domain\PackagingFinder\PackagingFinderResult;
use App\Domain\PackagingFinder\RepositoryPackagingFinder;
use App\Domain\Product;
use App\Domain\Products;
use App\Infrastructure\Doctrine\DoctrinePackagingAssignmentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RepositoryPackagingFinderTest extends TestCase
{
    private DoctrinePackagingAssignmentRepository&MockObject $assignmentRepository;
    private RepositoryPackagingFinder $packagingFinder;

    protected function setUp(): void
    {
        $this->assignmentRepository = $this->createMock(DoctrinePackagingAssignmentRepository::class);
        $this->packagingFinder = new RepositoryPackagingFinder($this->assignmentRepository);
    }

    public function testItReturnsHitFromRepository(): void
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
            PackagingFinderResult::createHit($packaging, RepositoryPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }

    public function testItReturnsMissWhenRepositoryHasNoAssignment(): void
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
            PackagingFinderResult::createMiss(RepositoryPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }
}
