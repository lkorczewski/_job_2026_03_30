<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Domain\PackagingAssignment;
use App\Domain\PackagingAssignmentRepository;
use App\Domain\Product;
use App\Domain\Products;
use App\Infrastructure\Doctrine\Entity\Packaging as PackagingEntity;
use App\Infrastructure\Doctrine\Entity\PackagingAssignment as PackagingAssignmentEntity;
use App\Infrastructure\Doctrine\Entity\PackagingAssignmentProduct as PackagingAssignmentProductEntity;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePackagingAssignmentRepository implements PackagingAssignmentRepository
{
    public function __construct(
        private ProductSignatureCalculator $productSignatureCalculator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function save(PackagingAssignment $packagingAssignment): void
    {
        $packagingEntity = $packagingAssignment->packaging === null
            ? null
            : $this->entityManager
                ->getRepository(PackagingEntity::class)
                ->find($packagingAssignment->packaging->id);

        $products = iterator_to_array($packagingAssignment->products);

        $assignmentProducts = array_map(
            function (Product $product, int $position): PackagingAssignmentProductEntity {
                return new PackagingAssignmentProductEntity(
                    $position,
                    $this->productSignatureCalculator->calculate($product),
                );
            },
            $products,
            array_keys($products),
        );

        $packagingAssignmentEntity = new PackagingAssignmentEntity($packagingEntity, ...$assignmentProducts);

        $this->entityManager->persist($packagingAssignmentEntity);
        $this->entityManager->flush();
    }

    public function findByProducts(Products $products): ?PackagingAssignment
    {
        $products = iterator_to_array($products);

        if ($products === []) {
            return null;
        }

        $queryBuilder = $this->entityManager
            ->getRepository(PackagingAssignmentEntity::class)
            ->createQueryBuilder('pa')
            ->leftJoin('pa.products', 'allProducts')
            ->groupBy('pa.id')
            ->having('COUNT(DISTINCT allProducts.id) = :productCount')
            ->setParameter('productCount', count($products));

        foreach ($products as $position => $product) {
            $alias = 'product' . $position;
            $signature = $this->productSignatureCalculator->calculate($product);

            $queryBuilder
                ->join(
                    'pa.products',
                    $alias,
                    'WITH',
                    sprintf(
                        '%s.position = :position%d AND %s.signature = :signature%d',
                        $alias,
                        $position,
                        $alias,
                        $position
                    ),
                )
                ->setParameter('position' . $position, $position)
                ->setParameter('signature' . $position, $signature);
        }

        $packagingAssignmentEntity = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        return $packagingAssignmentEntity
            ? new PackagingAssignment(new Products(...$products), $packagingAssignmentEntity->packaging?->toDomain())
            : null;
    }
}
