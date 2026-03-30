<?php

declare(strict_types=1);

namespace App\Domain\PackagingFinder;

use App\Domain\Products;

interface PackagingFinder
{
    public function findPackage(Products $products): PackagingFinderResult;
}
