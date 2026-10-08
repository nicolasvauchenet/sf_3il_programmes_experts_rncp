<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class PromotionDocument
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Promotion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public Promotion $promotion;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank, Assert\Length(max: 255)]
    public string $label = '';

    #[ORM\Column(length: 255)]
    public string $filename = '';

    #[ORM\Column(length: 255)]
    public string $originalName = '';

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    public int $position = 0;

    #[ORM\Column]
    public bool $visible = true;
}
