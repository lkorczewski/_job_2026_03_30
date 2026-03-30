<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(indexes: [new ORM\Index(name: 'idx_product_signature', columns: ['productSignature'])])]
class PackagingAssignment
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 1024)]
    public string $productSignature;

    #[ORM\ManyToOne(targetEntity: Packaging::class)]
    #[ORM\JoinColumn(nullable: true)]
    public ?Packaging $packaging;

    public function __construct(string $productSetSignature, ?Packaging $packaging = null)
    {
        $this->productSignature = $productSetSignature;
        $this->packaging = $packaging;
    }
}
