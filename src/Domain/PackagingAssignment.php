<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class PackagingAssignment
{
    public function __construct(public Products $products, public ?Packaging $packaging)
    {
    }
}
