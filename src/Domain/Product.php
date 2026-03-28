<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Product
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
        public float $weight,
    ) {
    }

    public function normalize(): self
    {
        $sizes = [$this->width, $this->height, $this->length];
        sort($sizes);

        return new self($sizes[0], $sizes[1], $sizes[2], $this->weight);
    }
}
