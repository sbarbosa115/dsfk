<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use App\Shared\Domain\Model\Audited;
use App\Shared\Domain\Model\References;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** An ordered stage of a project, with its budget lines, its weighted milestones and its dates. */
#[ORM\Entity]
#[ORM\Table(name: 'stage')]
class Stage implements Audited
{
    public const FULL_WEIGHT = 10000;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 20, enumType: StageStatus::class)]
    private StageStatus $status = StageStatus::Pending;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedEnd = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $actualStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $actualEnd = null;

    /** @var Collection<int, BudgetLine> */
    #[ORM\OneToMany(targetEntity: BudgetLine::class, mappedBy: 'stage', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $lines;

    /** @var Collection<int, Milestone> */
    #[ORM\OneToMany(targetEntity: Milestone::class, mappedBy: 'stage', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $milestones;

    public function __construct(
        #[ORM\Column(name: 'project_id')]
        #[References('project', onDelete: 'CASCADE')]
        private int $projectId,
        string $name,
        #[ORM\Column]
        private int $position,
    ) {
        $this->rename($name);
        $this->lines = new ArrayCollection();
        $this->milestones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getStatus(): StageStatus
    {
        return $this->status;
    }

    public function isCompleted(): bool
    {
        return StageStatus::Completed === $this->status;
    }

    public function getPlannedStart(): ?\DateTimeImmutable
    {
        return $this->plannedStart;
    }

    public function getPlannedEnd(): ?\DateTimeImmutable
    {
        return $this->plannedEnd;
    }

    public function getActualStart(): ?\DateTimeImmutable
    {
        return $this->actualStart;
    }

    public function getActualEnd(): ?\DateTimeImmutable
    {
        return $this->actualEnd;
    }

    /**
     * @return list<BudgetLine>
     */
    public function getLines(): array
    {
        return array_values($this->lines->toArray());
    }

    /**
     * @return list<Milestone>
     */
    public function getMilestones(): array
    {
        return array_values($this->milestones->toArray());
    }

    public function rename(string $name): void
    {
        if ('' === trim($name)) {
            throw InvalidValue::field('name', 'Este valor no debería estar vacío.');
        }
        $this->name = trim($name);
    }

    public function schedule(?\DateTimeImmutable $plannedStart, ?\DateTimeImmutable $plannedEnd): void
    {
        if (null !== $plannedStart && null !== $plannedEnd && $plannedEnd < $plannedStart) {
            throw InvalidValue::field('plannedEnd', 'La fecha de fin no puede ser anterior a la de inicio.');
        }
        $this->plannedStart = $plannedStart;
        $this->plannedEnd = $plannedEnd;
    }

    public function moveTo(int $position): void
    {
        $this->position = $position;
    }

    public function start(\DateTimeImmutable $date, \DateTimeImmutable $today): void
    {
        if (StageStatus::Pending !== $this->status) {
            throw new Conflict('stage_already_started');
        }
        if ($date->format('Y-m-d') > $today->format('Y-m-d')) {
            throw InvalidValue::field('actualStart', 'La fecha no puede estar en el futuro.');
        }
        $this->status = StageStatus::InProgress;
        $this->actualStart = $date;
    }

    /**
     * The stage is finished: it must be in progress with every milestone met. What happens to its money (the
     * carry-over) is the Finance context's job, in the same transaction.
     */
    public function complete(\DateTimeImmutable $date, \DateTimeImmutable $today): void
    {
        if (StageStatus::InProgress !== $this->status) {
            throw new Conflict('stage_not_in_progress');
        }
        if ($date->format('Y-m-d') > $today->format('Y-m-d')) {
            throw InvalidValue::field('actualEnd', 'La fecha no puede estar en el futuro.');
        }
        if (null !== $this->actualStart && $date < $this->actualStart) {
            throw InvalidValue::field('actualEnd', 'La fecha de fin no puede ser anterior al inicio de la etapa.');
        }
        foreach ($this->milestones as $milestone) {
            if (!$milestone->isCompleted()) {
                throw new Conflict('stage_milestones_pending');
            }
        }
        $this->status = StageStatus::Completed;
        $this->actualEnd = $date;
    }

    public function addLine(Category $category, string $description, string $unit, string $quantity, int $unitPrice): BudgetLine
    {
        $this->assertSameProject($category);
        $line = new BudgetLine($this, $category, $description, $unit, $quantity, $unitPrice, $this->nextPosition($this->lines));
        $this->lines->add($line);

        return $line;
    }

    public function changeLine(BudgetLine $line, Category $category, string $description, string $unit, string $quantity, int $unitPrice): void
    {
        $this->assertSameProject($category);
        $line->change($category, $description, $unit, $quantity, $unitPrice);
    }

    public function removeLine(BudgetLine $line): void
    {
        $this->lines->removeElement($line);
    }

    public function addMilestone(string $name, int $weight, ?\DateTimeImmutable $plannedDate): Milestone
    {
        self::assertWeightInRange($weight);
        if ($this->milestoneWeightTotal() + $weight > self::FULL_WEIGHT) {
            throw self::overweight();
        }
        $milestone = new Milestone($this, $name, $weight, $plannedDate, $this->nextPosition($this->milestones));
        $this->milestones->add($milestone);

        return $milestone;
    }

    public function reweigh(Milestone $milestone, int $weight): void
    {
        self::assertWeightInRange($weight);
        if ($this->milestoneWeightTotal() - $milestone->getWeight() + $weight > self::FULL_WEIGHT) {
            throw self::overweight();
        }
        $milestone->changeWeight($weight);
    }

    public function removeMilestone(Milestone $milestone): void
    {
        $this->milestones->removeElement($milestone);
    }

    public function milestone(int $id): Milestone
    {
        foreach ($this->milestones as $milestone) {
            if ($milestone->getId() === $id) {
                return $milestone;
            }
        }

        throw new NotFound('milestone_not_found');
    }

    /** Budgeted amount of the stage, in minor units. */
    public function budgetTotal(): int
    {
        return array_sum(array_map(static fn (BudgetLine $l): int => $l->getTotal(), $this->getLines()));
    }

    /** Sum of the milestone weights, in basis points. */
    public function milestoneWeightTotal(): int
    {
        return array_sum(array_map(static fn (Milestone $m): int => $m->getWeight(), $this->getMilestones()));
    }

    /** Physical progress in basis points: the weights of the milestones met. */
    public function progress(): int
    {
        return array_sum(array_map(static fn (Milestone $m): int => $m->isCompleted() ? $m->getWeight() : 0, $this->getMilestones()));
    }

    private function assertSameProject(Category $category): void
    {
        if ($category->getProjectId() !== $this->projectId) {
            throw InvalidValue::field('categoryId', 'La categoría no es de este proyecto.');
        }
    }

    private static function assertWeightInRange(int $weight): void
    {
        if ($weight < 1 || $weight > self::FULL_WEIGHT) {
            throw InvalidValue::field('weight', 'El peso debe estar entre 0,01 % y 100 %.');
        }
    }

    private static function overweight(): InvalidValue
    {
        return InvalidValue::field('weight', 'La suma de los pesos de la etapa no puede superar el 100 %.');
    }

    /**
     * @param Collection<int, BudgetLine>|Collection<int, Milestone> $items
     */
    private function nextPosition(Collection $items): int
    {
        $next = 0;
        foreach ($items as $item) {
            $next = max($next, $item->getPosition() + 1);
        }

        return $next;
    }

    public function auditProjectId(): ?int
    {
        return $this->projectId;
    }
}
