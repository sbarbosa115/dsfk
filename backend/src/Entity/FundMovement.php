<?php

namespace App\Entity;

use App\Enum\LedgerAccount;
use App\Enum\MovementType;
use App\Enum\PaymentMethod;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A movement of project money: a deposit split into allocations, a contingency draw
 * or a carry-over. Its entries are the signed amounts per account; balances are the
 * sum of entries of non-voided movements. Movements are never deleted, only voided.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_movement_project_date', fields: ['project', 'date'])]
class FundMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 30, enumType: MovementType::class)]
    private MovementType $type;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    /** Total moved, minor units, always positive. */
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $amount;

    #[ORM\Column(length: 20, nullable: true, enumType: PaymentMethod::class)]
    private ?PaymentMethod $method = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $voidedAt = null;

    #[ORM\ManyToOne]
    private ?User $voidedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $voidReason = null;

    /** Set when the movement touches the caja menor. */
    #[ORM\ManyToOne]
    private ?PettyCashCycle $pettyCashCycle = null;

    /** @var Collection<int, LedgerEntry> */
    #[ORM\OneToMany(targetEntity: LedgerEntry::class, mappedBy: 'movement', cascade: ['persist'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $entries;

    /** @var Collection<int, Attachment> */
    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'movement')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $attachments;

    public function __construct(Project $project, MovementType $type, \DateTimeImmutable $date, User $createdBy, ?string $note = null)
    {
        $this->project = $project;
        $this->type = $type;
        $this->date = $date;
        $this->createdBy = $createdBy;
        $this->note = null === $note || '' === trim($note) ? null : trim($note);
        $this->createdAt = new \DateTimeImmutable();
        $this->amount = 0;
        $this->entries = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function addEntry(LedgerAccount $account, int $amount, ?Stage $stage = null, ?Category $category = null): void
    {
        $this->entries->add(new LedgerEntry($this, $account, $amount, $stage, $category));
        if ($amount > 0) {
            $this->amount = $this->getAmount() + $amount;
        }
    }

    public function setPayment(?PaymentMethod $method, ?string $reference): void
    {
        $this->method = $method;
        $this->reference = null === $reference || '' === trim($reference) ? null : trim($reference);
    }

    public function void(User $by, string $reason): void
    {
        if ($this->isVoided()) {
            throw new \DomainException('movement_already_voided');
        }
        $this->voidedAt = new \DateTimeImmutable();
        $this->voidedBy = $by;
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

    public function setPettyCashCycle(PettyCashCycle $cycle): void
    {
        $this->pettyCashCycle = $cycle;
    }

    public function getPettyCashCycle(): ?PettyCashCycle
    {
        return $this->pettyCashCycle;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
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

    public function getCreatedBy(): User
    {
        return $this->createdBy;
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

    public function getVoidedBy(): ?User
    {
        return $this->voidedBy;
    }

    public function getVoidReason(): ?string
    {
        return $this->voidReason;
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    /**
     * @return Collection<int, Attachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }
}
