<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackageFinder;

use App\Domain\PackageFinder\CompositePackageFinder;
use App\Domain\PackageFinder\PackageFinder;
use App\Domain\PackageFinder\PackageFinderResult;
use App\Domain\Packaging;
use App\Domain\Products;
use PHPUnit\Framework\TestCase;

final class CompositePackageFinderTest extends TestCase
{
    public function testItReturnsFirstHitAndStopsProcessing(): void
    {
        $products = new Products();
        $expectedResult = PackageFinderResult::createHit(
            new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 400),
            'api',
        );

        $firstFinder = $this->createMock(PackageFinder::class);
        $firstFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn(PackageFinderResult::createMiss('repository'));

        $secondFinder = $this->createMock(PackageFinder::class);
        $secondFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($expectedResult);

        $thirdFinder = $this->createMock(PackageFinder::class);
        $thirdFinder
            ->expects(self::never())
            ->method('findPackage');

        $finder = new CompositePackageFinder($firstFinder, $secondFinder, $thirdFinder);

        self::assertSame($expectedResult, $finder->findPackage($products));
    }

    public function testItReturnsLastMissWhenNothingMatches(): void
    {
        $products = new Products();
        $firstMiss = PackageFinderResult::createMiss('repository');
        $secondMiss = PackageFinderResult::createMiss('api');

        $firstFinder = $this->createMock(PackageFinder::class);
        $firstFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($firstMiss);

        $secondFinder = $this->createMock(PackageFinder::class);
        $secondFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($secondMiss);

        $finder = new CompositePackageFinder($firstFinder, $secondFinder);

        self::assertSame($secondMiss, $finder->findPackage($products));
    }
}
