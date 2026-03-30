<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\Packaging;

final readonly class PackagingFinderResult
{
    public const string HIT = 'hit';
    public const string MISS = 'miss';

    public function __construct(
        public string $status,
        public ?Packaging $packaging,
        public string $source
    ) {
    }

    public static function createHit(?Packaging $packaging, string $source): self
    {
        return new self(self::HIT, $packaging, $source);
    }

    public static function createMiss(string $source): self
    {
        return new self(self::MISS, null, $source);
    }
}
