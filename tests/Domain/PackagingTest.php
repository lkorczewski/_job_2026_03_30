<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Packaging;
use PHPUnit\Framework\TestCase;

final class PackagingTest extends TestCase
{
    public function testNormalizeSortsDimensionsAndKeepsMaxWeight(): void
    {
        $packaging = new Packaging(id: null, width: 300, height: 100, length: 200, maxWeight: 400);

        $normalizedPackaging = $packaging->normalize();

        self::assertNotSame($normalizedPackaging, $packaging);
        self::assertEquals(
            new Packaging(id: null, width: 100, height: 200, length: 300, maxWeight: 400),
            $normalizedPackaging
        );
    }
}
