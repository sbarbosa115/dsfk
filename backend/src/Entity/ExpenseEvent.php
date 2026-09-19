<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Expense history: CREATED, EDITED (with the previous values), PM_APPROVED, APPROVED,
 * REJECTED, REIMBURSED, VOIDED.
 */
#[ORM\Entity]
class ExpenseEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Expense $expense;

    #[ORM\Column(length: 20)]
    private string $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment;

    /** Values before an edit. */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $previous;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Expense $expense, string $type, User $user, ?string $comment = null, ?array $previous = null)
    {
        $this->expense = $expense;
        $this->type = $type;
        $this->user = $user;
        $this->comment = $comment;
        $this->previous = $previous;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getPrevious(): ?array
    {
        return $this->previous;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
