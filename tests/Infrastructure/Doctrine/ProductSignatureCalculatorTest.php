<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Domain\Product;
use App\Infrastructure\Doctrine\ProductSignatureCalculator;
use PHPUnit\Framework\TestCase;

final class ProductSignatureCalculatorTest extends TestCase
{
    public function testCalculatingSingleProductSignature(): void
    {
        self::assertSame(
            '300x100x200@400',
            new ProductSignatureCalculator()->calculate(
                new Product(width: 300, height: 100, length: 200, weight: 400),
            ),
        );
    }
}
