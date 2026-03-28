<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Packaging
{
    public function __construct(
        public ?int $id,
        public float $width,
        public float $height,
        public float $length,
        public float $maxWeight
    ) {
    }

    public function getVolume(): float
    {
        return $this->length * $this->width * $this->height;
    }

    public function normalize(): self
    {
        $sizes = [$this->width, $this->height, $this->length];
        sort($sizes);

        return new self(id: null, width: $sizes[0], height: $sizes[1], length: $sizes[2], maxWeight: $this->maxWeight);
    }
}
