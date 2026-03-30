<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Product;

final readonly class ProductSignatureCalculator
{
    public function calculate(Product $product): string
    {
        return sprintf(
            '%gx%gx%g@%g',
            $product->width,
            $product->height,
            $product->length,
            $product->weight,
        );
    }
}
