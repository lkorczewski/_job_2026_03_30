<?php

namespace App\Domain;

use RuntimeException;

interface ApiConnector
{
    /** @throws RuntimeException */
    public function postPackagingRequest(Packagings $packagings, Products $products): ?Packaging;
}
