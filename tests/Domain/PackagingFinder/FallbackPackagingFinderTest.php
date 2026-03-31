<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackagingFinder;

use App\Domain\Packaging;
use App\Domain\PackagingFinder\FallbackPackagingFinder;
use App\Domain\PackagingFinder\PackagingFinderResult;
use App\Domain\Packagings;
use App\Domain\PackagingsRepository;
use App\Domain\Product;
use App\Domain\Products;
use App\Infrastructure\Doctrine\DoctrinePackagingsRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FallbackPackagingFinderTest extends TestCase
{
    private PackagingsRepository & MockObject $packagingsRepository;
    private FallbackPackagingFinder $packagingFinder;

    protected function setUp(): void
    {
        $this->packagingsRepository = $this->createMock(PackagingsRepository::class);
        $this->packagingFinder = new FallbackPackagingFinder($this->packagingsRepository);
    }

    #[DataProvider('provideSingleProductSelections')]
    public function testSingleProduct(Products $products, Packagings $packagings, ?Packaging $expectedPackaging): void
    {
        $this->packagingsRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn($packagings);

        self::assertEquals(
            PackagingFinderResult::createHit($expectedPackaging, FallbackPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }

    /**
     * @return iterable<string, array{0: Products, 1: Packagings, 2: ?Packaging}>
     */
    public static function provideSingleProductSelections(): iterable
    {
        $smallestFittingPackaging = new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 500);

        yield 'rotated product fits and smallest packaging is selected' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
            ),
            new Packagings(
                new Packaging(id: 2, width: 150, height: 200, length: 300, maxWeight: 500),
                $smallestFittingPackaging,
                new Packaging(id: 3, width: 90, height: 200, length: 300, maxWeight: 500),
            ),
            $smallestFittingPackaging,
        ];

        yield 'product does not fit in any packaging' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
            ),
            new Packagings(
                new Packaging(id: 1, width: 90, height: 200, length: 300, maxWeight: 500),
                new Packaging(id: 2, width: 100, height: 190, length: 300, maxWeight: 500),
            ),
            null,
        ];
    }

    #[DataProvider('provideEmptySelections')]
    public function testUnhandledCases(Products $products): void
    {
        $this->packagingsRepository->expects(self::never())->method('findAll');

        self::assertEquals(
            PackagingFinderResult::createMiss(FallbackPackagingFinder::class),
            $this->packagingFinder->findPackage($products),
        );
    }

    /**
     * @return iterable<string, array{0: Products}>
     */
    public static function provideEmptySelections(): iterable
    {
        yield 'empty products' => [new Products()];
        yield 'two products' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
                new Product(width: 120, height: 80, length: 100, weight: 200),
            ),
        ];
    }
}
