<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Money\MinorUnits;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** One line of a stage's budget: quantity × unit price, in a category. */
#[ORM\Entity]
#[ORM\Table(name: 'budget_line')]
class BudgetLine implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(length: 255)]
    private string $description;

    /** Free text: m², m³, kg, day, lump sum… */
    #[ORM\Column(length: 20)]
    private string $unit;

    /** Up to 3 decimals, as a decimal string. */
    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 3)]
    private string $quantity;

    /** Minor units. */
    #[ORM\Column(type: Types::BIGINT)]
    private int $unitPrice;

    /** Minor units: quantity × unit price, stored so reports stay stable. */
    #[ORM\Column(type: Types::BIGINT)]
    private int $total;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'lines')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Stage $stage,
        Category $category,
        string $description,
        string $unit,
        string $quantity,
        int $unitPrice,
        #[ORM\Column]
        private int $position,
    ) {
        $this->change($category, $description, $unit, $quantity, $unitPrice);
    }

    public function change(Category $category, string $description, string $unit, string $quantity, int $unitPrice): void
    {
        $this->category = $category;
        $this->description = trim($description);
        $this->unit = trim($unit);
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->total = MinorUnits::multiply($unitPrice, $quantity);
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

    /** "12.5", not "12.500". */
    public function getQuantity(): string
    {
        return str_contains($this->quantity, '.') ? rtrim(rtrim($this->quantity, '0'), '.') : $this->quantity;
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

    public function auditProjectId(): ?int
    {
        return $this->stage->getProjectId();
    }
}
