<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A caja menor period. Every movement touching the caja menor belongs to the open cycle. The PM closes it
 * (usually when the money runs out); the Admin signs it off. The closing balance opens the next cycle.
 */
#[ORM\Entity]
#[ORM\Table(name: 'petty_cash_cycle')]
#[ORM\UniqueConstraint(name: 'uniq_cycle_number', columns: ['project_id', 'number'])]
class PettyCashCycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: CycleStatus::class)]
    private CycleStatus $status = CycleStatus::Open;

    /** Snapshot taken when the cycle is closed. */
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $closingBalance = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(name: 'closed_by_id', nullable: true)]
    #[References('user')]
    private ?int $closedById = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $closingNote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $signedOffAt = null;

    #[ORM\Column(name: 'signed_off_by_id', nullable: true)]
    #[References('user')]
    private ?int $signedOffById = null;

    #[ORM\Column(type: Types::BIGINT)]
    private int $openingBalance;

    public function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        #[ORM\Column]
        private int $number,
        int $openingBalance,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        private \DateTimeImmutable $openedAt,
    ) {
        $this->openingBalance = $openingBalance;
    }

    public function close(int $by, int $closingBalance, ?string $note, \DateTimeImmutable $now): void
    {
        if (CycleStatus::Open !== $this->status) {
            throw new Conflict('cycle_not_open');
        }
        $this->status = CycleStatus::Closed;
        $this->closingBalance = $closingBalance;
        $this->closedAt = $now;
        $this->closedById = $by;
        $this->closingNote = null === $note || '' === trim($note) ? null : trim($note);
    }

    public function signOff(int $by, \DateTimeImmutable $now): void
    {
        if (CycleStatus::Closed !== $this->status) {
            throw new Conflict('cycle_not_closed');
        }
        $this->status = CycleStatus::SignedOff;
        $this->signedOffAt = $now;
        $this->signedOffById = $by;
    }

    public function isOpen(): bool
    {
        return CycleStatus::Open === $this->status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
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

    public function getClosedById(): ?int
    {
        return $this->closedById;
    }

    public function getClosingNote(): ?string
    {
        return $this->closingNote;
    }

    public function getSignedOffAt(): ?\DateTimeImmutable
    {
        return $this->signedOffAt;
    }

    public function getSignedOffById(): ?int
    {
        return $this->signedOffById;
    }
}
