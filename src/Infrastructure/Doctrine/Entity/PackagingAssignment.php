<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackagingAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Packaging::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Packaging $packaging;

    /** @var Collection<int, PackagingAssignmentProduct> */
    #[ORM\OneToMany(
        targetEntity: PackagingAssignmentProduct::class,
        mappedBy: 'packagingAssignment',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $products;

    public function __construct(
        ?Packaging $packaging = null,
        PackagingAssignmentProduct ...$products,
    ) {
        $this->packaging = $packaging;
        $this->products = new ArrayCollection();

        foreach ($products as $product) {
            $this->products->add($product);
            $product->setPackagingAssignment($this);
        }
    }
}
