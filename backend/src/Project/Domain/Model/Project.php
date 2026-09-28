<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Shared\Domain\Error\Conflict;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A construction project and who works on it. Its currency is fixed at creation; its status moves to ACTIVE
 * when the budget is approved and is otherwise set by an Admin.
 */
#[ORM\Entity]
#[ORM\Table(name: 'project')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** ISO 4217 code, fixed once the project exists. */
    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(length: 20, enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $plannedEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ProjectMember> */
    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $members;

    public function __construct(string $name, string $currency, \DateTimeImmutable $createdAt)
    {
        $this->rename($name);
        $this->currency = strtoupper($currency);
        $this->createdAt = $createdAt;
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function getPlannedStart(): ?\DateTimeImmutable
    {
        return $this->plannedStart;
    }

    public function getPlannedEnd(): ?\DateTimeImmutable
    {
        return $this->plannedEnd;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return list<ProjectMember>
     */
    public function getMembers(): array
    {
        return array_values($this->members->toArray());
    }

    public function rename(string $name): void
    {
        if ('' === trim($name)) {
            throw InvalidValue::field('name', 'Este valor no debería estar vacío.');
        }
        $this->name = trim($name);
    }

    public function describe(?string $description): void
    {
        $this->description = null === $description || '' === trim($description) ? null : $description;
    }

    public function schedule(?\DateTimeImmutable $plannedStart, ?\DateTimeImmutable $plannedEnd): void
    {
        if (null !== $plannedStart && null !== $plannedEnd && $plannedEnd < $plannedStart) {
            throw InvalidValue::field('plannedEnd', 'La fecha de fin no puede ser anterior a la de inicio.');
        }
        $this->plannedStart = $plannedStart;
        $this->plannedEnd = $plannedEnd;
    }

    /** The currency cannot change: every amount of the project is in it. Sending the same one is fine. */
    public function keepCurrency(string $currency): void
    {
        if (strtoupper($currency) !== $this->currency) {
            throw new InvalidValue('currency_locked');
        }
    }

    public function changeStatus(ProjectStatus $status): void
    {
        $this->status = $status;
    }

    /** The budget was approved: work can start. */
    public function activate(): void
    {
        if (ProjectStatus::Draft === $this->status) {
            $this->status = ProjectStatus::Active;
        }
    }

    /**
     * Adds a person with a role, or changes the role of someone already in the project. A project has at
     * most one Project Manager, who holds its caja menor.
     */
    public function assign(int $userId, ProjectRole $role): void
    {
        $current = $this->memberFor($userId);
        if (ProjectRole::ProjectManager === $role) {
            foreach ($this->members as $member) {
                if ($member !== $current && ProjectRole::ProjectManager === $member->getRole()) {
                    throw new Conflict('project_manager_exists');
                }
            }
        }

        if (null === $current) {
            $this->members->add(new ProjectMember($this, $userId, $role));
        } else {
            $current->changeRole($role);
        }
    }

    public function removeMember(int $memberId): void
    {
        foreach ($this->members as $member) {
            if ($member->getId() === $memberId) {
                $this->members->removeElement($member);

                return;
            }
        }

        throw new NotFound('member_not_found');
    }

    public function roleOf(int $userId): ?ProjectRole
    {
        return $this->memberFor($userId)?->getRole();
    }

    private function memberFor(int $userId): ?ProjectMember
    {
        foreach ($this->members as $member) {
            if ($member->getUserId() === $userId) {
                return $member;
            }
        }

        return null;
    }
}
