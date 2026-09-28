<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A movement of project money: a deposit split into allocations, a contingency draw or a carry-over (expenses
 * and reimbursements come with the Expense context). Its entries are the signed amounts per account; balances
 * are the sum of the entries of movements that are not voided. Movements are never deleted, only voided.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fund_movement')]
#[ORM\Index(name: 'idx_movement_project_date', columns: ['project_id', 'date'])]
class FundMovement implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Total moved, minor units, always positive. */
    #[ORM\Column(type: Types::BIGINT)]
    private int $amount = 0;

    #[ORM\Column(length: 20, nullable: true, enumType: PaymentMethod::class)]
    private ?PaymentMethod $method = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $voidedAt = null;

    #[ORM\Column(name: 'voided_by_id', nullable: true)]
    #[References('user')]
    private ?int $voidedById = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $voidReason = null;

    /** Set when the movement touches the caja menor. */
    #[ORM\ManyToOne]
    private ?PettyCashCycle $pettyCashCycle = null;

    /** @var Collection<int, LedgerEntry> */
    #[ORM\OneToMany(targetEntity: LedgerEntry::class, mappedBy: 'movement', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $entries;

    private function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        #[ORM\Column(length: 30, enumType: MovementType::class)]
        private MovementType $type,
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $date,
        ?string $note,
        #[ORM\Column(name: 'created_by_id')]
        #[References('user')]
        private int $createdById,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
    ) {
        if ($date->format('Y-m-d') > $createdAt->format('Y-m-d')) {
            throw InvalidValue::field('date', 'La fecha no puede estar en el futuro.');
        }
        $this->note = self::clean($note);
        $this->entries = new ArrayCollection();
    }

    /** A deposit with no allocations yet: allocate() splits it among the accounts. */
    public static function deposit(int $projectId, \DateTimeImmutable $date, ?PaymentMethod $method, ?string $reference, ?string $note, int $by, \DateTimeImmutable $now): self
    {
        $movement = new self($projectId, MovementType::Deposit, $date, $note, $by, $now);
        $movement->method = $method;
        $movement->reference = self::clean($reference);

        return $movement;
    }

    /** Contingency money moved into a stage: never more than the contingency holds. */
    public static function contingencyDraw(int $projectId, int $stageId, int $amount, Balances $balances, \DateTimeImmutable $date, ?string $reason, int $by, \DateTimeImmutable $now): self
    {
        if ($amount > $balances->of(LedgerAccount::Contingency)) {
            throw InvalidValue::field('amount', 'El monto supera el saldo disponible de la contingencia.');
        }
        $movement = new self($projectId, MovementType::ContingencyDraw, $date, $reason, $by, $now);
        $movement->addEntry(LedgerAccount::Contingency, -$amount);
        $movement->addEntry(LedgerAccount::Stage, $amount, $stageId);

        return $movement;
    }

    /** The leftover of a completed stage, moved to the next open stage, or to the contingency after the last one. */
    public static function carryOver(int $projectId, int $fromStageId, ?int $toStageId, int $amount, \DateTimeImmutable $date, int $by, \DateTimeImmutable $now): self
    {
        $movement = new self($projectId, MovementType::Carryover, $date, null, $by, $now);
        $movement->addEntry(LedgerAccount::Stage, -$amount, $fromStageId);
        if (null === $toStageId) {
            $movement->addEntry(LedgerAccount::Contingency, $amount);
        } else {
            $movement->addEntry(LedgerAccount::Stage, $amount, $toStageId);
        }

        return $movement;
    }

    /**
     * An expense paid from a stage (earmarked with its category) or from the caja menor. The account must hold the
     * amount: money never goes below zero.
     *
     * @throws InvalidValue insufficient_funds, with the minor units `available`
     */
    public static function spend(int $projectId, LedgerAccount $from, ?int $stageId, ?int $categoryId, int $amount, Balances $balances, \DateTimeImmutable $date, string $description, int $by, \DateTimeImmutable $now): self
    {
        if (LedgerAccount::Contingency === $from) {
            throw new \LogicException('Expenses are paid from a stage or the caja menor.');
        }
        self::assertFunds($balances->balance(Balances::key($from, $stageId)), $amount);
        $movement = new self($projectId, MovementType::Expense, $date, $description, $by, $now);
        $movement->addEntry($from, -$amount, LedgerAccount::Stage === $from ? $stageId : null, LedgerAccount::Stage === $from ? $categoryId : null);
        $movement->amount = $amount;

        return $movement;
    }

    /**
     * Team Lead expenses paid back from the caja menor, as one movement.
     *
     * @throws InvalidValue insufficient_funds
     */
    public static function reimbursement(int $projectId, int $total, Balances $balances, \DateTimeImmutable $date, string $note, int $by, \DateTimeImmutable $now): self
    {
        self::assertFunds($balances->of(LedgerAccount::PettyCash), $total);
        $movement = new self($projectId, MovementType::Reimbursement, $date, $note, $by, $now);
        $movement->addEntry(LedgerAccount::PettyCash, -$total);
        $movement->amount = $total;

        return $movement;
    }

    /** Part of a deposit going to one account. */
    public function allocate(LedgerAccount $account, int $amount, ?int $stageId = null, ?int $categoryId = null): void
    {
        if ($amount <= 0) {
            throw InvalidValue::field('amount', 'Debe ser un monto positivo.');
        }
        $this->addEntry($account, $amount, $stageId, $categoryId);
    }

    /**
     * Deposits and draws can be voided (with a reason), as long as no account ends up below zero, e.g. a deposit
     * whose money was already drawn or carried over. Carry-overs follow from completing a stage; expenses and
     * reimbursements are voided through their expense. A movement of a closed caja menor cycle is final.
     *
     * @param Balances $current the project's balances with this movement still counted
     */
    public function void(Balances $current, int $by, string $reason, \DateTimeImmutable $now): void
    {
        if (!\in_array($this->type, [MovementType::Deposit, MovementType::ContingencyDraw], true)) {
            throw new Conflict('movement_not_voidable');
        }
        $this->cancel($current, $by, $reason, $now);
    }

    /** Voiding an expense gives its money back to where it came from (the Expense context voids the expense). */
    public function voidSpending(Balances $current, int $by, string $reason, \DateTimeImmutable $now): void
    {
        if (MovementType::Expense !== $this->type) {
            throw new Conflict('movement_not_voidable');
        }
        $this->cancel($current, $by, $reason, $now);
    }

    private function cancel(Balances $current, int $by, string $reason, \DateTimeImmutable $now): void
    {
        if ($this->isVoided()) {
            throw new Conflict('movement_already_voided');
        }
        if (null !== $this->pettyCashCycle && !$this->pettyCashCycle->isOpen()) {
            throw new Conflict('cycle_closed');
        }
        $effect = [];
        foreach ($this->entries as $entry) {
            $effect[$entry->key()] = ($effect[$entry->key()] ?? 0) + $entry->getAmount();
        }
        foreach ($effect as $key => $amount) {
            if ($current->balance($key) - $amount < 0) {
                throw new Conflict('void_would_overdraw');
            }
        }
        if ('' === trim($reason)) {
            throw InvalidValue::field('reason', 'Este valor no debería estar vacío.');
        }
        $this->voidedAt = $now;
        $this->voidedById = $by;
        $this->voidReason = trim($reason);
    }

    public function touchesPettyCash(): bool
    {
        foreach ($this->entries as $entry) {
            if (LedgerAccount::PettyCash === $entry->getAccount()) {
                return true;
            }
        }

        return false;
    }

    public function assignTo(PettyCashCycle $cycle): void
    {
        $this->pettyCashCycle = $cycle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getType(): MovementType
    {
        return $this->type;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getAmount(): int
    {
        return (int) $this->amount;
    }

    public function getMethod(): ?PaymentMethod
    {
        return $this->method;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getCreatedById(): int
    {
        return $this->createdById;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isVoided(): bool
    {
        return null !== $this->voidedAt;
    }

    public function getVoidedAt(): ?\DateTimeImmutable
    {
        return $this->voidedAt;
    }

    public function getVoidedById(): ?int
    {
        return $this->voidedById;
    }

    public function getVoidReason(): ?string
    {
        return $this->voidReason;
    }

    public function getPettyCashCycle(): ?PettyCashCycle
    {
        return $this->pettyCashCycle;
    }

    /**
     * @return list<LedgerEntry>
     */
    public function getEntries(): array
    {
        return array_values($this->entries->toArray());
    }

    private function addEntry(LedgerAccount $account, int $amount, ?int $stageId = null, ?int $categoryId = null): void
    {
        $this->entries->add(new LedgerEntry($this, $account, $amount, $stageId, $categoryId));
        if ($amount > 0) {
            $this->amount = $this->getAmount() + $amount;
        }
    }

    private static function assertFunds(int $available, int $amount): void
    {
        if ($amount > $available) {
            throw new InvalidValue('insufficient_funds', ['available' => max(0, $available)]);
        }
    }

    private static function clean(?string $text): ?string
    {
        return null === $text || '' === trim($text) ? null : trim($text);
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
