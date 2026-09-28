<?php

declare(strict_types=1);

namespace App\Expense\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotAllowed;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Money spent on the project. The PM and Admins pay from a stage or the caja menor, and it counts at once; a Team
 * Lead pays out of pocket, the PM approves it (an Admin too above the Team Lead limit), and it is paid back from
 * the caja menor later. Expenses are never deleted: a mistake is voided.
 */
#[ORM\Entity]
#[ORM\Table(name: 'expense')]
#[ORM\Index(name: 'idx_expense_project_status', columns: ['project_id', 'status'])]
class Expense implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'stage_id')]
    #[References('stage')]
    private int $stageId;

    #[ORM\Column(name: 'category_id')]
    #[References('category')]
    private int $categoryId;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    /** Minor units. */
    #[ORM\Column(type: Types::BIGINT)]
    private int $amount;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $supplier = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20, enumType: ExpenseStatus::class)]
    private ExpenseStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    /** Finance's EXPENSE movement that took the money out of the stage or the caja menor. */
    #[ORM\Column(name: 'movement_id', nullable: true, unique: true)]
    #[References('fund_movement')]
    private ?int $movementId = null;

    #[ORM\ManyToOne]
    private ?Reimbursement $reimbursement = null;

    /** @var Collection<int, ExpenseEvent> */
    #[ORM\OneToMany(targetEntity: ExpenseEvent::class, mappedBy: 'expense', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $events;

    private function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        #[ORM\Column(length: 20, enumType: PaidFrom::class)]
        private PaidFrom $paidFrom,
        /** Who paid: the PM or Admin recording it, or the Team Lead to pay back. */
        #[ORM\Column(name: 'paid_by_id')]
        #[References('user')]
        private int $paidById,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
    ) {
        $this->status = PaidFrom::OutOfPocket === $paidFrom ? ExpenseStatus::Submitted : ExpenseStatus::Approved;
        $this->events = new ArrayCollection();
    }

    public static function record(
        int $projectId,
        int $stageId,
        int $categoryId,
        \DateTimeImmutable $date,
        int $amount,
        string $description,
        ?string $supplier,
        ?string $invoiceNumber,
        PaidFrom $paidFrom,
        int $by,
        \DateTimeImmutable $now,
    ): self {
        $expense = new self($projectId, $paidFrom, $by, $now);
        $expense->describe($stageId, $categoryId, $date, $amount, $description, $supplier, $invoiceNumber, $now);
        $expense->log('CREATED', $by, $now);

        return $expense;
    }

    /**
     * Its owner corrects a pending or rejected expense; it goes back to the PM.
     *
     * @param array{stage: string, category: string} $names of the stage and category it had, for the history
     */
    public function correct(int $by, int $stageId, int $categoryId, \DateTimeImmutable $date, int $amount, string $description, ?string $supplier, ?string $invoiceNumber, array $names, \DateTimeImmutable $now): void
    {
        if ($by !== $this->paidById) {
            throw new NotAllowed('forbidden');
        }
        if (!$this->isCorrectable()) {
            throw new Conflict('expense_not_editable');
        }
        $previous = [
            'stage' => $names['stage'],
            'category' => $names['category'],
            'date' => $this->date->format('Y-m-d'),
            'amount' => $this->getAmount(),
            'description' => $this->description,
            'supplier' => $this->supplier,
            'invoiceNumber' => $this->invoiceNumber,
        ];
        $this->describe($stageId, $categoryId, $date, $amount, $description, $supplier, $invoiceNumber, $now);
        $this->status = ExpenseStatus::Submitted;
        $this->rejectionReason = null;
        $this->log('EDITED', $by, $now, previous: $previous);
    }

    /**
     * A PM approves up to the Team Lead limit; above it the expense waits for an Admin, who approves anything.
     *
     * @param int $limit the Team Lead limit, minor units
     */
    public function approve(int $by, bool $isAdmin, int $limit, bool $hasReceipt, \DateTimeImmutable $now): void
    {
        $this->assertStatus(ExpenseStatus::Submitted, ExpenseStatus::PmApproved);
        if (!$hasReceipt) {
            throw new Conflict('receipt_required');
        }
        if (!$isAdmin && ExpenseStatus::PmApproved === $this->status) {
            throw new Conflict('expense_awaiting_admin');
        }
        if (!$isAdmin && $this->getAmount() > $limit) {
            $this->status = ExpenseStatus::PmApproved;
            $this->log('PM_APPROVED', $by, $now);

            return;
        }
        $this->status = ExpenseStatus::Approved;
        $this->log('APPROVED', $by, $now);
    }

    public function reject(int $by, bool $isAdmin, string $reason, \DateTimeImmutable $now): void
    {
        $this->assertStatus(ExpenseStatus::Submitted, ExpenseStatus::PmApproved);
        if (!$isAdmin && ExpenseStatus::PmApproved === $this->status) {
            throw new Conflict('expense_awaiting_admin');
        }
        if ('' === trim($reason)) {
            throw InvalidValue::field('reason', 'Este valor no debería estar vacío.');
        }
        $this->status = ExpenseStatus::Rejected;
        $this->rejectionReason = trim($reason);
        $this->log('REJECTED', $by, $now, trim($reason));
    }

    /** An approved expense recorded by mistake; one already paid back stays. */
    public function void(int $by, string $reason, \DateTimeImmutable $now): void
    {
        $this->assertStatus(ExpenseStatus::Approved);
        if (null !== $this->reimbursement) {
            throw new Conflict('expense_invalid_status');
        }
        if ('' === trim($reason)) {
            throw InvalidValue::field('reason', 'Este valor no debería estar vacío.');
        }
        $this->status = ExpenseStatus::Voided;
        $this->log('VOIDED', $by, $now, trim($reason));
    }

    public function markReimbursed(Reimbursement $reimbursement, int $by, \DateTimeImmutable $now): void
    {
        if (!$this->isReimbursable()) {
            throw new Conflict('expense_not_reimbursable');
        }
        $this->status = ExpenseStatus::Reimbursed;
        $this->reimbursement = $reimbursement;
        $this->log('REIMBURSED', $by, $now);
    }

    public function linkMovement(int $movementId): void
    {
        $this->movementId = $movementId;
    }

    public function isSpent(): bool
    {
        return $this->status->isSpent();
    }

    /** Its owner may still change it (and add receipts). */
    public function isCorrectable(): bool
    {
        return \in_array($this->status, [ExpenseStatus::Submitted, ExpenseStatus::Rejected], true);
    }

    public function isReimbursable(): bool
    {
        return PaidFrom::OutOfPocket === $this->paidFrom && ExpenseStatus::Approved === $this->status;
    }

    public function isWaitingFor(bool $isAdmin): bool
    {
        return ExpenseStatus::Submitted === $this->status || ($isAdmin && ExpenseStatus::PmApproved === $this->status);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getStageId(): int
    {
        return $this->stageId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): int
    {
        return (int) $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getPaidFrom(): PaidFrom
    {
        return $this->paidFrom;
    }

    public function getPaidById(): int
    {
        return $this->paidById;
    }

    public function getStatus(): ExpenseStatus
    {
        return $this->status;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getMovementId(): ?int
    {
        return $this->movementId;
    }

    public function getReimbursement(): ?Reimbursement
    {
        return $this->reimbursement;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return list<ExpenseEvent>
     */
    public function getEvents(): array
    {
        return array_values($this->events->toArray());
    }

    private function describe(int $stageId, int $categoryId, \DateTimeImmutable $date, int $amount, string $description, ?string $supplier, ?string $invoiceNumber, \DateTimeImmutable $now): void
    {
        if ($date->format('Y-m-d') > $now->format('Y-m-d')) {
            throw InvalidValue::field('date', 'La fecha no puede estar en el futuro.');
        }
        if ($amount <= 0) {
            throw InvalidValue::field('amount', 'Debe ser un monto positivo.');
        }
        if ('' === trim($description)) {
            throw InvalidValue::field('description', 'Este valor no debería estar vacío.');
        }
        $this->stageId = $stageId;
        $this->categoryId = $categoryId;
        $this->date = $date;
        $this->amount = $amount;
        $this->description = trim($description);
        $this->supplier = self::clean($supplier);
        $this->invoiceNumber = self::clean($invoiceNumber);
    }

    /**
     * @param array<string, mixed>|null $previous
     */
    private function log(string $type, int $by, \DateTimeImmutable $now, ?string $comment = null, ?array $previous = null): void
    {
        $this->events->add(new ExpenseEvent($this, $type, $by, $now, $comment, $previous));
    }

    private function assertStatus(ExpenseStatus ...$allowed): void
    {
        if (!\in_array($this->status, $allowed, true)) {
            throw new Conflict('expense_invalid_status');
        }
    }

    private static function clean(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : trim($value);
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
