<?php

declare(strict_types=1);

namespace App\Domain;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Product>
 */
final readonly class Products implements IteratorAggregate, Countable
{
    /**
     * @var list<Product>
     */
    private array $products;

    public function __construct(Product ...$products)
    {
        $this->products = $products;
    }

    /**
     * @return Traversable<int, Product>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->products);
    }

    public function count(): int
    {
        return count($this->products);
    }

    public function normalize(): self
    {
        $normalizedProducts = array_map(
            static fn (Product $product): Product => $product->normalize(),
            $this->products,
        );

        usort($normalizedProducts, static function (Product $left, Product $right): int {
            return [$left->width, $left->height, $left->length, $left->weight]
                <=>
                [$right->width, $right->height, $right->length, $right->weight];
        });

        return new self(...$normalizedProducts);
    }
}
