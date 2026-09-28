<?php

declare(strict_types=1);

namespace App\Planning\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Model\References;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** A weighted step of a stage. The weights of a stage add up to 100 %; progress is what is met. */
#[ORM\Entity]
#[ORM\Table(name: 'milestone')]
class Milestone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $name;

    /** Share of the stage in basis points (2500 = 25 %). */
    #[ORM\Column]
    private int $weight;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'completed_by_id', nullable: true)]
    #[References('user')]
    private ?int $completedById = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $completionNotes = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'milestones')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Stage $stage,
        string $name,
        int $weight,
        ?\DateTimeImmutable $plannedDate,
        #[ORM\Column]
        private int $position,
    ) {
        $this->rename($name);
        $this->weight = $weight;
        $this->plannedDate = $plannedDate;
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

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getPlannedDate(): ?\DateTimeImmutable
    {
        return $this->plannedDate;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getCompletedById(): ?int
    {
        return $this->completedById;
    }

    public function getCompletionNotes(): ?string
    {
        return $this->completionNotes;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isCompleted(): bool
    {
        return null !== $this->completedAt;
    }

    /** Not met, and its planned date has gone by. */
    public function isOverdue(\DateTimeImmutable $today): bool
    {
        return !$this->isCompleted() && null !== $this->plannedDate && $this->plannedDate->format('Y-m-d') < $today->format('Y-m-d');
    }

    public function rename(string $name): void
    {
        if ('' === trim($name)) {
            throw InvalidValue::field('name', 'Este valor no debería estar vacío.');
        }
        $this->name = trim($name);
    }

    public function planFor(?\DateTimeImmutable $date): void
    {
        $this->plannedDate = $date;
    }

    /** Stage::reweigh() checks the stage's total before calling this. */
    public function changeWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function complete(\DateTimeImmutable $date, int $byUserId, ?string $notes, \DateTimeImmutable $today): void
    {
        if ($this->isCompleted()) {
            throw new Conflict('milestone_already_completed');
        }
        if ($date->format('Y-m-d') > $today->format('Y-m-d')) {
            throw InvalidValue::field('completedAt', 'La fecha no puede estar en el futuro.');
        }
        $this->completedAt = $date;
        $this->completedById = $byUserId;
        $this->completionNotes = null === $notes || '' === trim($notes) ? null : trim($notes);
    }

    public function reopen(): void
    {
        $this->completedAt = null;
        $this->completedById = null;
        $this->completionNotes = null;
    }
}
