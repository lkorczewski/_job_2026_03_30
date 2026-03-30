<?php

declare(strict_types=1);

namespace App\Tests\Domain\PackagingFinder;

use App\Domain\Packaging;
use App\Domain\PackagingFinder\CompositePackagingFinder;
use App\Domain\PackagingFinder\PackagingFinder;
use App\Domain\PackagingFinder\PackagingFinderResult;
use App\Domain\Products;
use PHPUnit\Framework\TestCase;

final class CompositePackagingFinderTest extends TestCase
{
    public function testItReturnsFirstHitAndStopsProcessing(): void
    {
        $products = new Products();
        $expectedResult = PackagingFinderResult::createHit(
            new Packaging(id: 1, width: 100, height: 200, length: 300, maxWeight: 400),
            'api',
        );

        $firstFinder = $this->createMock(PackagingFinder::class);
        $firstFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn(PackagingFinderResult::createMiss('repository'));

        $secondFinder = $this->createMock(PackagingFinder::class);
        $secondFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($expectedResult);

        $thirdFinder = $this->createMock(PackagingFinder::class);
        $thirdFinder
            ->expects(self::never())
            ->method('findPackage');

        $finder = new CompositePackagingFinder($firstFinder, $secondFinder, $thirdFinder);

        self::assertSame($expectedResult, $finder->findPackage($products));
    }

    public function testItReturnsLastMissWhenNothingMatches(): void
    {
        $products = new Products();
        $firstMiss = PackagingFinderResult::createMiss('repository');
        $secondMiss = PackagingFinderResult::createMiss('api');

        $firstFinder = $this->createMock(PackagingFinder::class);
        $firstFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($firstMiss);

        $secondFinder = $this->createMock(PackagingFinder::class);
        $secondFinder
            ->expects(self::once())
            ->method('findPackage')
            ->with($products)
            ->willReturn($secondMiss);

        $finder = new CompositePackagingFinder($firstFinder, $secondFinder);

        self::assertSame($secondMiss, $finder->findPackage($products));
    }
}
