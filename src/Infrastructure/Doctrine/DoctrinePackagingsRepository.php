<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Packaging;
use App\Domain\Packagings;
use App\Domain\PackagingsRepository;
use App\Infrastructure\Doctrine\Entity\Packaging as PackagingEntity;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePackagingsRepository implements PackagingsRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findAll(): Packagings
    {
        return new Packagings(
            ...array_map(
                fn (PackagingEntity $packaging): Packaging => $packaging->toDomain(),
                $this->entityManager->getRepository(PackagingEntity::class)->findAll()
            )
        );
    }
}
