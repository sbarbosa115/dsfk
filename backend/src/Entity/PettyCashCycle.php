<?php

namespace App\Entity;

use App\Enum\CycleStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A caja menor period. Every movement touching the caja menor belongs to the open cycle.
 * The PM closes it (usually when money runs out); the Admin signs it off.
 * The closing balance becomes the next cycle's opening balance.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'uniq_cycle_number', fields: ['project', 'number'])]
class PettyCashCycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column]
    private int $number;

    #[ORM\Column(length: 20, enumType: CycleStatus::class)]
    private CycleStatus $status = CycleStatus::Open;

    #[ORM\Column(type: Types::BIGINT)]
    private string|int $openingBalance;

    /** Snapshot taken when the cycle is closed. */
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private string|int|null $closingBalance = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $openedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne]
    private ?User $closedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $closingNote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $signedOffAt = null;

    #[ORM\ManyToOne]
    private ?User $signedOffBy = null;

    public function __construct(Project $project, int $number, int $openingBalance)
    {
        $this->project = $project;
        $this->number = $number;
        $this->openingBalance = $openingBalance;
        $this->openedAt = new \DateTimeImmutable();
    }

    public function close(User $by, int $closingBalance, ?string $note): void
    {
        if (CycleStatus::Open !== $this->status) {
            throw new \DomainException('cycle_not_open');
        }
        $this->status = CycleStatus::Closed;
        $this->closingBalance = $closingBalance;
        $this->closedAt = new \DateTimeImmutable();
        $this->closedBy = $by;
        $this->closingNote = null === $note || '' === trim($note) ? null : trim($note);
    }

    public function signOff(User $by): void
    {
        if (CycleStatus::Closed !== $this->status) {
            throw new \DomainException('cycle_not_closed');
        }
        $this->status = CycleStatus::SignedOff;
        $this->signedOffAt = new \DateTimeImmutable();
        $this->signedOffBy = $by;
    }

    public function isOpen(): bool
    {
        return CycleStatus::Open === $this->status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getStatus(): CycleStatus
    {
        return $this->status;
    }

    public function getOpeningBalance(): int
    {
        return (int) $this->openingBalance;
    }

    public function getClosingBalance(): ?int
    {
        return null === $this->closingBalance ? null : (int) $this->closingBalance;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function getClosingNote(): ?string
    {
        return $this->closingNote;
    }

    public function getSignedOffAt(): ?\DateTimeImmutable
    {
        return $this->signedOffAt;
    }

    public function getSignedOffBy(): ?User
    {
        return $this->signedOffBy;
    }
}
