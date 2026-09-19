<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Milestone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'milestones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Stage $stage;

    #[ORM\Column(length: 200)]
    private string $name;

    /** Share of the stage in basis points (2500 = 25%). Weights of a stage sum to 10000. */
    #[ORM\Column]
    private int $weight;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne]
    private ?User $completedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $completionNotes = null;

    #[ORM\Column]
    private int $position;

    public function __construct(Stage $stage, string $name, int $weight, int $position)
    {
        $this->stage = $stage;
        $this->name = trim($name);
        $this->weight = $weight;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStage(): Stage
    {
        return $this->stage;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getPlannedDate(): ?\DateTimeImmutable
    {
        return $this->plannedDate;
    }

    public function setPlannedDate(?\DateTimeImmutable $plannedDate): void
    {
        $this->plannedDate = $plannedDate;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isCompleted(): bool
    {
        return null !== $this->completedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCompletedBy(): ?User
    {
        return $this->completedBy;
    }

    public function getCompletionNotes(): ?string
    {
        return $this->completionNotes;
    }

    public function complete(\DateTimeImmutable $date, User $by, ?string $notes): void
    {
        if ($this->isCompleted()) {
            throw new \DomainException('milestone_already_completed');
        }
        $this->completedAt = $date;
        $this->completedBy = $by;
        $this->completionNotes = null === $notes || '' === trim($notes) ? null : trim($notes);
    }

    public function reopen(): void
    {
        $this->completedAt = null;
        $this->completedBy = null;
        $this->completionNotes = null;
    }
}
