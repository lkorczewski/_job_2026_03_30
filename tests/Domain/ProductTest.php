<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testNormalizeSortsDimensionsAndKeepsWeight(): void
    {
        $product = new Product(width: 300, height: 100, length: 200, weight: 400);

        $normalizedProduct = $product->normalize();

        self::assertNotSame($normalizedProduct, $product);
        self::assertEquals(new Product(width: 100, height: 200, length: 300, weight: 400), $normalizedProduct);
    }
}
