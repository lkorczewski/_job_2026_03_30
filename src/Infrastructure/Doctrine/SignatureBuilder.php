<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Product;
use App\Domain\Products;

final readonly class SignatureBuilder
{
    public function calculate(Products $products): string
    {
        $productSignatures = array_map(
            static fn (Product $product): string => sprintf(
                '%gx%gx%g@%g',
                $product->width,
                $product->height,
                $product->length,
                $product->weight,
            ),
            iterator_to_array($products),
        );

        return implode('|', $productSignatures);
    }
}
