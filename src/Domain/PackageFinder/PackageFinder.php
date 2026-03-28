<?php

declare(strict_types=1);

namespace App\Domain\PackageFinder;

use App\Domain\Products;

interface PackageFinder
{
    public function findPackage(Products $products): PackageFinderResult;
}
