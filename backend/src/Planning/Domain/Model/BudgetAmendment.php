<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Model\Audited;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * An addition to an approved budget (Adición): new stages, with their lines and milestones, that the Admin approves
 * apart. The stages approved before it never change. It goes through the budget's own statuses.
 */
#[ORM\Entity]
#[ORM\Table(name: 'budget_amendment')]
#[ORM\UniqueConstraint(name: 'uniq_amendment_number', columns: ['budget_id', 'number'])]
class BudgetAmendment implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: BudgetStatus::class)]
    private BudgetStatus $status = BudgetStatus::Draft;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'amendments')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Budget $budget,
        /** 1 for the budget's first amendment, then 2… */
        #[ORM\Column]
        private int $number,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
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

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getStatus(): BudgetStatus
    {
        return $this->status;
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isApproved(): bool
    {
        return BudgetStatus::Approved === $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    /**
     * @throws Conflict budget_locked
     */
    public function assertEditable(): void
    {
        if (!$this->isEditable()) {
            throw new Conflict('budget_locked');
        }
    }

    /** Only the Budget moves it, recording the event. */
    public function moveTo(BudgetStatus $status, \DateTimeImmutable $at): void
    {
        $this->status = $status;
        if (BudgetStatus::Approved === $status) {
            $this->approvedAt = $at;
        }
    }

    public function auditProjectId(): ?int
    {
        return $this->budget->getProjectId();
    }
}
