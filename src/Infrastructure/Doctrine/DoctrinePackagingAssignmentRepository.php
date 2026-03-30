<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\Exception\TooManyProducts;
use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Products;
use App\Infrastructure\Doctrine\Entity\Packaging as PackagingEntity;
use App\Infrastructure\Doctrine\Entity\PackagingAssignment as PackagingAssignmentEntity;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePackagingAssignmentRepository implements PackagingAssignmentRepository
{
    public function __construct(
        private SignatureBuilder $signatureCalculator,
        private EntityManagerInterface $entityManager
    ) {
    }

    /** @throws TooManyProducts */
    public function save(PackagingAssignment $packagingAssignment): void
    {
        $packagingEntity = $this->entityManager
            ->getRepository(PackagingEntity::class)
            ->find($packagingAssignment->packaging->id);

        $packagingAssignment = new PackagingAssignmentEntity(
            $this->signatureCalculator->calculate($packagingAssignment->products),
            $packagingEntity,
        );

        $this->entityManager->persist($packagingAssignment);
        $this->entityManager->flush();
    }

    /** @throws TooManyProducts */
    public function findByProducts(Products $products): ?PackagingAssignment
    {
        $signature = $this->signatureCalculator->calculate($products);

        $packagingAssignmentEntity = $this->entityManager
            ->getRepository(PackagingAssignmentEntity::class)
            ->findOneBy(['productSignature' => $signature]);

        return $packagingAssignmentEntity
            ? new PackagingAssignment($products, $packagingAssignmentEntity->packaging?->toDomain())
            : null;
    }
}
