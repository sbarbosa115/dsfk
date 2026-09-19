<?php

namespace App\Entity;

use App\Enum\ExpenseStatus;
use App\Enum\PaidFrom;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Money spent on the project. Counts against the budget once APPROVED; never deleted.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_expense_project_status', fields: ['project', 'status'])]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Stage $stage;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    /** Minor units. */
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $amount;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $supplier = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20, enumType: PaidFrom::class)]
    private PaidFrom $paidFrom;

    /** Who paid: the PM/Admin recording it, or the Team Lead to reimburse. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $paidBy;

    #[ORM\Column(length: 20, enumType: ExpenseStatus::class)]
    private ExpenseStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    /** Ledger movement taking the money out of the stage or caja menor. */
    #[ORM\OneToOne]
    private ?FundMovement $movement = null;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    private ?Reimbursement $reimbursement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ExpenseEvent> */
    #[ORM\OneToMany(targetEntity: ExpenseEvent::class, mappedBy: 'expense', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $events;

    /** @var Collection<int, Attachment> */
    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'expense')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $attachments;

    public function __construct(Project $project, PaidFrom $paidFrom, User $paidBy)
    {
        $this->project = $project;
        $this->paidFrom = $paidFrom;
        $this->paidBy = $paidBy;
        $this->status = PaidFrom::OutOfPocket === $paidFrom ? ExpenseStatus::Submitted : ExpenseStatus::Approved;
        $this->createdAt = new \DateTimeImmutable();
        $this->events = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function setDetails(Stage $stage, Category $category, \DateTimeImmutable $date, int $amount, string $description, ?string $supplier, ?string $invoiceNumber): void
    {
        $this->stage = $stage;
        $this->category = $category;
        $this->date = $date;
        $this->amount = $amount;
        $this->description = trim($description);
        $this->supplier = self::clean($supplier);
        $this->invoiceNumber = self::clean($invoiceNumber);
    }

    /** @return array<string, mixed> values shown in the history when an expense is edited */
    public function snapshot(): array
    {
        return [
            'stage' => $this->stage->getName(),
            'category' => $this->category->getName(),
            'date' => $this->date->format('Y-m-d'),
            'amount' => $this->getAmount(),
            'description' => $this->description,
            'supplier' => $this->supplier,
            'invoiceNumber' => $this->invoiceNumber,
        ];
    }

    public function record(string $type, User $by, ?string $comment = null, ?array $previous = null): void
    {
        $this->events->add(new ExpenseEvent($this, $type, $by, $comment, $previous));
    }

    public function isEditableByOwner(): bool
    {
        return \in_array($this->status, [ExpenseStatus::Submitted, ExpenseStatus::Rejected], true);
    }

    public function resubmit(): void
    {
        if (!$this->isEditableByOwner()) {
            throw new \DomainException('expense_not_editable');
        }
        $this->status = ExpenseStatus::Submitted;
        $this->rejectionReason = null;
    }

    public function markPmApproved(): void
    {
        $this->assertStatus(ExpenseStatus::Submitted);
        $this->status = ExpenseStatus::PmApproved;
    }

    public function approve(): void
    {
        $this->assertStatus(ExpenseStatus::Submitted, ExpenseStatus::PmApproved);
        $this->status = ExpenseStatus::Approved;
    }

    public function reject(string $reason): void
    {
        $this->assertStatus(ExpenseStatus::Submitted, ExpenseStatus::PmApproved);
        $this->status = ExpenseStatus::Rejected;
        $this->rejectionReason = trim($reason);
    }

    public function markReimbursed(Reimbursement $reimbursement): void
    {
        if (PaidFrom::OutOfPocket !== $this->paidFrom) {
            throw new \DomainException('expense_not_reimbursable');
        }
        $this->assertStatus(ExpenseStatus::Approved);
        $this->status = ExpenseStatus::Reimbursed;
        $this->reimbursement = $reimbursement;
    }

    public function void(): void
    {
        $this->assertStatus(ExpenseStatus::Approved);
        $this->status = ExpenseStatus::Voided;
    }

    public function linkMovement(FundMovement $movement): void
    {
        $this->movement = $movement;
    }

    private function assertStatus(ExpenseStatus ...$allowed): void
    {
        if (!\in_array($this->status, $allowed, true)) {
            throw new \DomainException('expense_invalid_status');
        }
    }

    private static function clean(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : trim($value);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function getCategory(): Category
    {
        return $this->category;
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

    public function getPaidBy(): User
    {
        return $this->paidBy;
    }

    public function getStatus(): ExpenseStatus
    {
        return $this->status;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getMovement(): ?FundMovement
    {
        return $this->movement;
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
     * @return Collection<int, ExpenseEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /**
     * @return Collection<int, Attachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }
}
