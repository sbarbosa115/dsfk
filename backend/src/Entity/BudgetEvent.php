<?php

namespace App\Entity;

use App\Enum\BudgetStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * History of budget transitions (who submitted, returned or approved it, and why).
 */
#[ORM\Entity]
class BudgetEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Budget $budget;

    /** The status the budget moved to. */
    #[ORM\Column(length: 20, enumType: BudgetStatus::class)]
    private BudgetStatus $status;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Budget $budget, BudgetStatus $status, User $user, ?string $comment = null)
    {
        $this->budget = $budget;
        $this->status = $status;
        $this->user = $user;
        $this->comment = $comment;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getStatus(): BudgetStatus
    {
        return $this->status;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
