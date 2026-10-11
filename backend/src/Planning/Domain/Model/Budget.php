<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One per project. The PM drafts it; the Admin approves it, and then it is locked for good: spending may pass
 * it, and overruns are flagged, never hidden. While it is submitted or approved nobody edits it, the Admin
 * included. An approved budget grows only through amendments (Adiciones): new stages the Admin approves apart, one
 * open amendment at a time, through the same submit, return and approve.
 */
#[ORM\Entity]
#[ORM\Table(name: 'budget')]
class Budget implements Audited
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: BudgetStatus::class)]
    private BudgetStatus $status = BudgetStatus::Draft;

    /** Contingency reserve in minor units, kept apart from the stages. */
    #[ORM\Column(type: Types::BIGINT)]
    private int $contingency = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $approvedAt = null;

    /** @var Collection<int, BudgetEvent> */
    #[ORM\OneToMany(targetEntity: BudgetEvent::class, mappedBy: 'budget', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $events;

    /** @var Collection<int, BudgetAmendment> */
    #[ORM\OneToMany(targetEntity: BudgetAmendment::class, mappedBy: 'budget', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $amendments;

    public function __construct(
        #[ORM\Column(name: 'project_id', unique: true)]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
    ) {
        $this->events = new ArrayCollection();
        $this->amendments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
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

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    /**
     * @return list<BudgetEvent>
     */
    public function getEvents(): array
    {
        return array_values($this->events->toArray());
    }

    /**
     * @return list<BudgetAmendment>
     */
    public function getAmendments(): array
    {
        return array_values($this->amendments->toArray());
    }

    /** The amendment not approved yet (a draft, submitted or returned one), if any: there is never more than one. */
    public function openAmendment(): ?BudgetAmendment
    {
        foreach ($this->amendments as $amendment) {
            if (!$amendment->isApproved()) {
                return $amendment;
            }
        }

        return null;
    }

    /**
     * Where a new stage goes: nowhere special while the budget is being written (null), else into the open
     * amendment, opened on the spot once the budget is approved.
     *
     * @throws Conflict budget_locked while the budget or the amendment is waiting for the Admin
     */
    public function amendmentToEdit(\DateTimeImmutable $at): ?BudgetAmendment
    {
        if ($this->isEditable()) {
            return null;
        }
        if (!$this->isApproved()) {
            throw new Conflict('budget_locked');
        }
        $open = $this->openAmendment();
        if (null !== $open) {
            $open->assertEditable();

            return $open;
        }
        $number = 1 + array_reduce($this->getAmendments(), static fn (int $max, BudgetAmendment $a): int => max($max, $a->getNumber()), 0);
        $amendment = new BudgetAmendment($this, $number, $at);
        $this->amendments->add($amendment);

        return $amendment;
    }

    /** An amendment emptied of its stages goes, with its history. */
    public function dropAmendment(BudgetAmendment $amendment): void
    {
        $amendment->assertEditable();
        foreach ($this->getEvents() as $event) {
            if ($event->getAmendment() === $amendment) {
                $this->events->removeElement($event);
            }
        }
        $this->amendments->removeElement($amendment);
    }

    /**
     * A stage's lines, milestones and dates change only while what it belongs to (the budget, or its amendment) is
     * being written.
     *
     * @throws Conflict budget_locked
     */
    public function assertStageEditable(Stage $stage): void
    {
        $amendment = $stage->getAmendment();
        if (null === $amendment) {
            $this->assertEditable();
        } else {
            $amendment->assertEditable();
        }
    }

    /**
     * The stages the next submit or approval is about: all of them for the budget, only the open amendment's once
     * it is approved.
     *
     * @param list<Stage> $stages
     *
     * @return list<Stage>
     */
    public function underReview(array $stages): array
    {
        if (!$this->isApproved()) {
            return $stages;
        }
        $open = $this->openAmendment();

        return array_values(array_filter($stages, static fn (Stage $s): bool => null !== $open && $s->getAmendment() === $open));
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

    /**
     * @throws Conflict budget_not_approved
     */
    public function assertApproved(): void
    {
        if (!$this->isApproved()) {
            throw new Conflict('budget_not_approved');
        }
    }

    public function changeContingency(int $minor): void
    {
        $this->assertEditable();
        $this->contingency = $minor;
    }

    /**
     * Sends the budget, or once it is approved its open amendment, to the Admin.
     *
     * @param list<array{code: string, stageId?: int}> $issues what PlanCompleteness found in underReview()
     *
     * @return BudgetAmendment|null the amendment sent, null for the budget itself
     */
    public function submit(int $byUserId, array $issues, \DateTimeImmutable $at): ?BudgetAmendment
    {
        $amendment = $this->isApproved() ? ($this->openAmendment() ?? throw new Conflict('budget_locked')) : null;
        null === $amendment ? $this->assertEditable() : $amendment->assertEditable();
        self::assertComplete($issues);
        $this->move($amendment, BudgetStatus::Submitted, $byUserId, $at);

        return $amendment;
    }

    /**
     * @return BudgetAmendment|null the amendment returned, null for the budget itself
     */
    public function returnToDraft(int $byUserId, string $comment, \DateTimeImmutable $at): ?BudgetAmendment
    {
        $amendment = $this->underDecision();
        if ('' === trim($comment)) {
            throw InvalidValue::field('comment', 'This value should not be blank.');
        }
        $this->move($amendment, BudgetStatus::Returned, $byUserId, $at, trim($comment));

        return $amendment;
    }

    /**
     * @param list<array{code: string, stageId?: int}> $issues
     *
     * @return BudgetAmendment|null the amendment approved, null for the budget itself
     */
    public function approve(int $byUserId, array $issues, \DateTimeImmutable $at): ?BudgetAmendment
    {
        $amendment = $this->underDecision();
        self::assertComplete($issues);
        $this->move($amendment, BudgetStatus::Approved, $byUserId, $at);

        return $amendment;
    }

    /**
     * What the Admin is deciding on: the budget while it is submitted, else the submitted amendment.
     *
     * @throws Conflict budget_not_submitted
     */
    private function underDecision(): ?BudgetAmendment
    {
        if (BudgetStatus::Submitted === $this->status) {
            return null;
        }
        $open = $this->isApproved() ? $this->openAmendment() : null;
        if (null === $open || BudgetStatus::Submitted !== $open->getStatus()) {
            throw new Conflict('budget_not_submitted');
        }

        return $open;
    }

    private function move(?BudgetAmendment $amendment, BudgetStatus $status, int $byUserId, \DateTimeImmutable $at, ?string $comment = null): void
    {
        if (null === $amendment) {
            $this->status = $status;
            if (BudgetStatus::Approved === $status) {
                $this->approvedAt = $at;
            }
        } else {
            $amendment->moveTo($status, $at);
        }
        $this->events->add(new BudgetEvent($this, $status, $byUserId, $at, $comment, $amendment));
    }

    /**
     * @param list<array{code: string, stageId?: int}> $issues
     */
    private static function assertComplete(array $issues): void
    {
        if ([] !== $issues) {
            throw new InvalidValue('budget_incomplete', ['issues' => $issues]);
        }
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
