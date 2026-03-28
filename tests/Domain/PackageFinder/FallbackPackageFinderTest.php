<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackageFinder;

use App\Domain\PackageFinder\FallbackPackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\PackagingsRepository;
use App\Domain\Product;
use App\Domain\Products;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FallbackPackageFinderTest extends TestCase
{
    private PackagingsRepository & MockObject $packagingsRepository;
    private FallbackPackageFinder $packageFinder;

    public function setUp(): void
    {
        $this->packagingsRepository = $this->createMock(PackagingsRepository::class);
        $this->packageFinder = new FallbackPackageFinder($this->packagingsRepository);
    }

    #[DataProvider('provideSingleProductSelections')]
    public function testSingleProduct(
        Products $products,
        ?Packaging $expectedPackaging
    ): void {
        $this->packagingsRepository->expects(self::once())->method('findAll')->willReturn(
            new Packagings(
                new Packaging(id: null, width: 500, height: 500, length: 500, maxWeight: 1_000),
                new Packaging(id: null, width: 100, height: 300, length: 200, maxWeight: 500),
                new Packaging(id: null, width: 50, height: 50, length: 50, maxWeight: 500),
            )
        );

        $response = $this->packageFinder->findPackage($products);

        self::assertEquals(
            PackageFinderResult::createHit($expectedPackaging, FallbackPackageFinder::class),
            $response
        );
    }

    /**
     * @return iterable<string, array{0: Products, 1: ?Packaging}>
     */
    public static function provideSingleProductSelections(): iterable
    {
        yield 'slightly smaller' => [
            new Products(
                new Product(width: 299, height: 99, length: 199, weight: 400),
            ),
            new Packaging(id: null, width: 100, height: 300, length: 200, maxWeight: 500),
        ];

        yield 'exact fitting' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
            ),
            new Packaging(id: null, width: 100, height: 300, length: 200, maxWeight: 500),
        ];

        yield 'one dimension too big, two slightly smaller' => [
            new Products(
                new Product(width: 301, height: 99, length: 199, weight: 400),
            ),
            new Packaging(id: null, width: 500, height: 500, length: 500, maxWeight: 1_000),
        ];

        yield 'bigger than biggest box' => [
            new Products(
                new Product(width: 500, height: 600, length: 500, weight: 400),
            ),
            null,
        ];
    }

    #[DataProvider('provideEmptySelections')]
    public function testUnhandledCases(Products $products): void
    {
        $this->packagingsRepository->expects(self::never())->method('findAll');

        $response = $this->packageFinder->findPackage($products);

        self::assertEquals(
            PackageFinderResult::createMiss(FallbackPackageFinder::class),
            $response
        );
    }

    /**
     * @return iterable<string, array{0: Products}>
     */
    public static function provideEmptySelections(): iterable
    {
        yield 'empty product set' => [
            new Products(),
        ];

        yield 'two products' => [
            new Products(
                new Product(width: 100, height: 100, length: 100, weight: 100),
                new Product(width: 100, height: 100, length: 100, weight: 100),
            ),
        ];
    }
}
