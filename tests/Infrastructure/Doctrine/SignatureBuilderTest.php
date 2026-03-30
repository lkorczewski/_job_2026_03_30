<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Domain\Product;
use App\Domain\Products;
use App\Domain\Exception\TooManyProducts;
use App\Infrastructure\Doctrine\SignatureBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SignatureBuilderTest extends TestCase
{
    #[DataProvider('provideProductCollections')]
    public function testCalculatingSignature(Products $productSet, string $expectedSignature): void
    {
        self::assertSame($expectedSignature, new SignatureBuilder()->calculate($productSet));
    }

    /**
     * @return iterable<string, array{0: Products, 1: string}>
     */
    public static function provideProductCollections(): iterable
    {
        yield 'single product' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
            ),
            '300x100x200@400',
        ];

        yield 'multiple products' => [
            new Products(
                new Product(width: 300, height: 100, length: 200, weight: 400),
                new Product(width: 120, height: 80, length: 100, weight: 200),
                new Product(width: 140, height: 100, length: 120, weight: 200),
                new Product(width: 250, height: 100, length: 200, weight: 200),
                new Product(width: 300, height: 100, length: 200, weight: 200),
            ),
            '300x100x200@400|120x80x100@200|140x100x120@200|250x100x200@200|300x100x200@200',
        ];
    }

    public function testTooLongSignature(): void
    {
        $products = [];

        for ($i = 0; $i < 70; $i++) {
            $products[] = new Product(width: 300, height: 100, length: 200, weight: 400);
        }

        $this->expectException(TooManyProducts::class);
        $this->expectExceptionMessage('Too many products');

        new SignatureBuilder()->calculate(new Products(...$products));
    }
}
