<?php

namespace App\Entity;

use App\Enum\StageStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Stage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column]
    private int $position;

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
    private Collection $budgetLines;

    /** @var Collection<int, Milestone> */
    #[ORM\OneToMany(targetEntity: Milestone::class, mappedBy: 'stage', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $milestones;

    public function __construct(Project $project, string $name, int $position)
    {
        $this->project = $project;
        $this->name = trim($name);
        $this->position = $position;
        $this->budgetLines = new ArrayCollection();
        $this->milestones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getStatus(): StageStatus
    {
        return $this->status;
    }

    public function getPlannedStart(): ?\DateTimeImmutable
    {
        return $this->plannedStart;
    }

    public function setPlannedStart(?\DateTimeImmutable $plannedStart): void
    {
        $this->plannedStart = $plannedStart;
    }

    public function getPlannedEnd(): ?\DateTimeImmutable
    {
        return $this->plannedEnd;
    }

    public function setPlannedEnd(?\DateTimeImmutable $plannedEnd): void
    {
        $this->plannedEnd = $plannedEnd;
    }

    public function getActualStart(): ?\DateTimeImmutable
    {
        return $this->actualStart;
    }

    public function getActualEnd(): ?\DateTimeImmutable
    {
        return $this->actualEnd;
    }

    public function start(\DateTimeImmutable $date): void
    {
        if (StageStatus::Pending !== $this->status) {
            throw new \DomainException('stage_already_started');
        }
        $this->status = StageStatus::InProgress;
        $this->actualStart = $date;
    }

    public function complete(\DateTimeImmutable $date): void
    {
        if (StageStatus::InProgress !== $this->status) {
            throw new \DomainException('stage_not_in_progress');
        }
        if (null !== $this->actualStart && $date < $this->actualStart) {
            throw new \DomainException('stage_end_before_start');
        }
        foreach ($this->milestones as $milestone) {
            if (!$milestone->isCompleted()) {
                throw new \DomainException('stage_milestones_pending');
            }
        }
        $this->status = StageStatus::Completed;
        $this->actualEnd = $date;
    }

    public function isCompleted(): bool
    {
        return StageStatus::Completed === $this->status;
    }

    /**
     * @return Collection<int, BudgetLine>
     */
    public function getBudgetLines(): Collection
    {
        return $this->budgetLines;
    }

    public function addBudgetLine(BudgetLine $line): void
    {
        $this->budgetLines->add($line);
    }

    public function removeBudgetLine(BudgetLine $line): void
    {
        $this->budgetLines->removeElement($line);
    }

    /**
     * @return Collection<int, Milestone>
     */
    public function getMilestones(): Collection
    {
        return $this->milestones;
    }

    public function addMilestone(Milestone $milestone): void
    {
        $this->milestones->add($milestone);
    }

    public function removeMilestone(Milestone $milestone): void
    {
        $this->milestones->removeElement($milestone);
    }

    /** Budgeted amount of the stage in minor units. */
    public function getBudgetTotal(): int
    {
        $total = 0;
        foreach ($this->budgetLines as $line) {
            $total += $line->getTotal();
        }

        return $total;
    }

    /** Sum of milestone weights in basis points (10000 = 100%). */
    public function getMilestoneWeightTotal(): int
    {
        $total = 0;
        foreach ($this->milestones as $milestone) {
            $total += $milestone->getWeight();
        }

        return $total;
    }

    /** Physical progress in basis points: sum of the weights of completed milestones. */
    public function getProgress(): int
    {
        $progress = 0;
        foreach ($this->milestones as $milestone) {
            if ($milestone->isCompleted()) {
                $progress += $milestone->getWeight();
            }
        }

        return $progress;
    }
}
