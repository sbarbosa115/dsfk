<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\References;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One per project. The PM drafts it; the Admin approves it, and then it is locked for good: spending may pass
 * it, and overruns are flagged, never hidden. While it is submitted or approved nobody edits it, the Admin
 * included.
 */
#[ORM\Entity]
#[ORM\Table(name: 'budget')]
class Budget
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
    #[ORM\OneToMany(targetEntity: BudgetEvent::class, mappedBy: 'budget', cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $events;

    public function __construct(
        #[ORM\Column(name: 'project_id', unique: true)]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
    ) {
        $this->events = new ArrayCollection();
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
     * @param list<array{code: string, stageId?: int}> $issues what PlanCompleteness found
     */
    public function submit(int $byUserId, array $issues, \DateTimeImmutable $at): void
    {
        $this->assertEditable();
        self::assertComplete($issues);
        $this->status = BudgetStatus::Submitted;
        $this->events->add(new BudgetEvent($this, BudgetStatus::Submitted, $byUserId, $at));
    }

    public function returnToDraft(int $byUserId, string $comment, \DateTimeImmutable $at): void
    {
        $this->assertSubmitted();
        if ('' === trim($comment)) {
            throw InvalidValue::field('comment', 'Este valor no debería estar vacío.');
        }
        $this->status = BudgetStatus::Returned;
        $this->events->add(new BudgetEvent($this, BudgetStatus::Returned, $byUserId, $at, trim($comment)));
    }

    /**
     * @param list<array{code: string, stageId?: int}> $issues
     */
    public function approve(int $byUserId, array $issues, \DateTimeImmutable $at): void
    {
        $this->assertSubmitted();
        self::assertComplete($issues);
        $this->status = BudgetStatus::Approved;
        $this->approvedAt = $at;
        $this->events->add(new BudgetEvent($this, BudgetStatus::Approved, $byUserId, $at));
    }

    private function assertSubmitted(): void
    {
        if (BudgetStatus::Submitted !== $this->status) {
            throw new Conflict('budget_not_submitted');
        }
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
}
