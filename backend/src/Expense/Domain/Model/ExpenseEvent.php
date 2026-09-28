<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** One step of an expense's history: CREATED, EDITED (with the values before), PM_APPROVED, APPROVED, REJECTED, REIMBURSED, VOIDED. */
#[ORM\Entity]
#[ORM\Table(name: 'expense_event')]
class ExpenseEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @param array<string, mixed>|null $previous
     */
    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'events')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Expense $expense,
        #[ORM\Column(length: 20)]
        private string $type,
        #[ORM\Column(name: 'user_id')]
        #[References('user')]
        private int $userId,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $comment = null,
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private ?array $previous = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpense(): Expense
    {
        return $this->expense;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPrevious(): ?array
    {
        return $this->previous;
    }
}
