<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PackagingAssignmentProduct
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PackagingAssignment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private PackagingAssignment $packagingAssignment;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position;

    #[ORM\Column(type: Types::STRING)]
    private string $signature;

    public function __construct(int $position, string $signature)
    {
        $this->position = $position;
        $this->signature = $signature;
    }

    public function setPackagingAssignment(PackagingAssignment $packagingAssignment): void
    {
        $this->packagingAssignment = $packagingAssignment;
    }
}
