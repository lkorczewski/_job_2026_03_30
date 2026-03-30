<?php

declare(strict_types=1);

use App\Application;
use App\Domain\PackageFinder\ApiPackageFinder;
use App\Domain\PackageFinder\CompositePackageFinder;
use App\Domain\PackageFinder\FallbackPackageFinder;
use App\Domain\PackageFinder\RepositoryCachingWrapper;
use App\Domain\PackageFinder\RepositoryPackageFinder;
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

$packageFinder = new CompositePackageFinder(
    new RepositoryPackageFinder($packagingAssignmentRepository),
    new RepositoryCachingWrapper(
        new ApiPackageFinder($packagingsRepository, $connector),
        $packagingAssignmentRepository,
    ),
    new FallbackPackageFinder($packagingsRepository),
);

return new Application($packageFinder);
