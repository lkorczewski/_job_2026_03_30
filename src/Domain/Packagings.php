<?php

declare(strict_types=1);

namespace App\Domain;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Packaging>
 */
final readonly class Packagings implements IteratorAggregate, Countable
{
    /**
     * @var list<Packaging>
     */
    private array $packagings;

    public function __construct(Packaging ...$packagings)
    {
        $this->packagings = $packagings;
    }

    /**
     * @return Traversable<int, Packaging>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->packagings);
    }

    public function count(): int
    {
        return count($this->packagings);
    }

    public function getById(int $packagingId): ?Packaging
    {
        return array_find($this->packagings, fn($packaging) => $packaging->id === $packagingId);
    }
}
