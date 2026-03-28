<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Product;
use App\Domain\Products;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductsTest extends TestCase
{
    /**
     * @param list<Product> $expectedProducts
     */
    #[DataProvider('provideProductCollections')]
    public function testNormalizeReturnsNormalizedAndSortedProducts(Products $products, array $expectedProducts): void
    {
        $normalizedProducts = $products->normalize();

        self::assertNotSame($products, $normalizedProducts);
        self::assertEquals($expectedProducts, iterator_to_array($normalizedProducts));
    }

    /**
     * @return iterable<string, array{0: Products, 1: list<Product>}>
     */
    public static function provideProductCollections(): iterable
    {
        yield 'single product' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
            ),
            [
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];

        yield 'different minimal dimension' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
                new Product(width: 120, height: 100, length: 80, weight: 200),
            ),
            [
                new Product(width: 80, height: 100, length: 120, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];

        yield 'different second minimal dimension' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
                new Product(width: 140, height: 120, length: 100, weight: 200),
            ),
            [
                new Product(width: 100, height: 120, length: 140, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];

        yield 'different third minimal dimension' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
                new Product(width: 250, height: 200, length: 100, weight: 200),
            ),
            [
                new Product(width: 100, height: 200, length: 250, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];

        yield 'different fourth minimal dimension' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
                new Product(width: 300, height: 200, length: 100, weight: 200),
            ),
            [
                new Product(width: 100, height: 200, length: 300, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];

        yield 'differences in all dimensions' => [
            new Products(
                new Product(width: 300, height: 200, length: 100, weight: 400),
                new Product(width: 120, height: 100, length: 80, weight: 200),
                new Product(width: 140, height: 120, length: 100, weight: 200),
                new Product(width: 250, height: 200, length: 100, weight: 200),
                new Product(width: 300, height: 200, length: 100, weight: 200),
            ),
            [
                new Product(width: 80, height: 100, length: 120, weight: 200),
                new Product(width: 100, height: 120, length: 140, weight: 200),
                new Product(width: 100, height: 200, length: 250, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 200),
                new Product(width: 100, height: 200, length: 300, weight: 400),
            ],
        ];
    }
}
