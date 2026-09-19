<?php

namespace App\Entity;

use App\Service\MoneyConverter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BudgetLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'budgetLines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Stage $stage;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(length: 255)]
    private string $description;

    /** Free text: m², m³, kg, día, global... */
    #[ORM\Column(length: 20)]
    private string $unit;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 3)]
    private string $quantity;

    /** Minor units. */
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $unitPrice;

    /** Minor units, quantity × unit price. Stored so reports stay stable. */
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $total;

    #[ORM\Column]
    private int $position;

    public function __construct(Stage $stage, Category $category, string $description, string $unit, string $quantity, int $unitPrice, int $position)
    {
        $this->stage = $stage;
        $this->position = $position;
        $this->update($category, $description, $unit, $quantity, $unitPrice);
    }

    public function update(Category $category, string $description, string $unit, string $quantity, int $unitPrice): void
    {
        $this->category = $category;
        $this->description = trim($description);
        $this->unit = trim($unit);
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->total = MoneyConverter::multiply($unitPrice, $quantity);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitPrice(): int
    {
        return (int) $this->unitPrice;
    }

    public function getTotal(): int
    {
        return (int) $this->total;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
