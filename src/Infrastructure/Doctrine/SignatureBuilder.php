<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Product;
use App\Domain\Products;
use App\Domain\Exception\TooManyProducts;

final readonly class SignatureBuilder
{
    private const int MAX_LENGTH = 1024;

    /** @throws TooManyProducts */
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

        $signature = implode('|', $productSignatures);

        if (strlen($signature) > self::MAX_LENGTH) {
            throw new TooManyProducts('Too many products');
        }

        return $signature;
    }
}
