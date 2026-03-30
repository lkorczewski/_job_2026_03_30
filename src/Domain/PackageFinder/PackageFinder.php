<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\Exception\TooManyProducts;
use App\Domain\Products;

interface PackageFinder
{
    /** @throws TooManyProducts */
    public function findPackage(Products $products): PackageFinderResult;
}
