<?php

namespace App\Entity;

use App\Enum\BudgetStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One per project. Drafted by the PM, approved (and locked) by the Admin.
 */
#[ORM\Entity]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'budget')]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 20, enumType: BudgetStatus::class)]
    private BudgetStatus $status = BudgetStatus::Draft;

    /** Contingency reserve in minor units, tracked apart from the stages. */
    #[ORM\Column(type: Types::BIGINT)]
    private string|int $contingency = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    /** @var Collection<int, BudgetEvent> */
    #[ORM\OneToMany(targetEntity: BudgetEvent::class, mappedBy: 'budget', cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $events;

    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
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

    public function getContingency(): int
    {
        return (int) $this->contingency;
    }

    public function setContingency(int $contingency): void
    {
        $this->assertEditable();
        $this->contingency = $contingency;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    /**
     * @return Collection<int, BudgetEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function assertEditable(): void
    {
        if (!$this->isEditable()) {
            throw new \DomainException('budget_locked');
        }
    }

    public function submit(User $by): void
    {
        $this->assertEditable();
        $this->status = BudgetStatus::Submitted;
        $this->events->add(new BudgetEvent($this, BudgetStatus::Submitted, $by));
    }

    public function returnToDraft(User $by, string $comment): void
    {
        $this->assertSubmitted();
        $this->status = BudgetStatus::Returned;
        $this->events->add(new BudgetEvent($this, BudgetStatus::Returned, $by, $comment));
    }

    public function approve(User $by): void
    {
        $this->assertSubmitted();
        $this->status = BudgetStatus::Approved;
        $this->approvedAt = new \DateTimeImmutable();
        $this->events->add(new BudgetEvent($this, BudgetStatus::Approved, $by));
    }

    private function assertSubmitted(): void
    {
        if (BudgetStatus::Submitted !== $this->status) {
            throw new \DomainException('budget_not_submitted');
        }
    }
}
