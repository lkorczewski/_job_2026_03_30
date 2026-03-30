<?php

declare(strict_types=1);

use App\Application;
use App\Domain\PackagingFinder\ApiPackagingFinder;
use App\Domain\PackagingFinder\CompositePackagingFinder;
use App\Domain\PackagingFinder\FallbackPackagingFinder;
use App\Domain\PackagingFinder\RepositoryCachingPackagingFinder;
use App\Domain\PackagingFinder\RepositoryPackagingFinder;
use App\Infrastructure\Doctrine\DoctrinePackagingAssignmentRepository;
use App\Infrastructure\Doctrine\DoctrinePackagingsRepository;
use App\Infrastructure\Doctrine\ProductSignatureCalculator;
use App\Infrastructure\Janedbal\JanedbalApiConnector;
use GuzzleHttp\Client;

require_once __DIR__ . '/../../vendor/autoload.php';

$entityManager = require __DIR__ . '/doctrine.php';

$connector = new JanedbalApiConnector(new Client(), 'https://binpacking.janedbal.cz/api/v1/pack');
$signatureCalculator = new ProductSignatureCalculator();
$packagingAssignmentRepository = new DoctrinePackagingAssignmentRepository($signatureCalculator, $entityManager);
$packagingsRepository = new DoctrinePackagingsRepository($entityManager);

$packagingFinder = new CompositePackagingFinder(
    new RepositoryPackagingFinder($packagingAssignmentRepository),
    new RepositoryCachingPackagingFinder(
        new ApiPackagingFinder($packagingsRepository, $connector),
        $packagingAssignmentRepository,
    ),
    new FallbackPackagingFinder($packagingsRepository),
);

return new Application($packagingFinder);
