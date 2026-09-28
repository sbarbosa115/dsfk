<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** One transition of the budget: who moved it to which status, when, and the Admin's comment on a return. */
#[ORM\Entity]
#[ORM\Table(name: 'budget_event')]
class BudgetEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'events')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Budget $budget,
        #[ORM\Column(length: 20, enumType: BudgetStatus::class)]
        private BudgetStatus $status,
        #[ORM\Column(name: 'user_id')]
        #[References('user')]
        private int $userId,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $comment = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBudget(): Budget
    {
        return $this->budget;
    }

    public function getStatus(): BudgetStatus
    {
        return $this->status;
    }

    public function getUserId(): int
    {
        return $this->userId;
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
