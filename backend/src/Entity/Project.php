<?php

namespace App\Entity;

use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['project:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Groups(['project:read'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['project:read'])]
    private ?string $description = null;

    /** ISO 4217 code, fixed once the project is created. */
    #[ORM\Column(length: 3)]
    #[Groups(['project:read'])]
    private string $currency;

    #[ORM\Column(length: 20, enumType: ProjectStatus::class)]
    #[Groups(['project:read'])]
    private ProjectStatus $status = ProjectStatus::Draft;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['project:read'])]
    private ?\DateTimeImmutable $plannedStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['project:read'])]
    private ?\DateTimeImmutable $plannedEnd = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['project:read'])]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ProjectMember> */
    #[Groups(['project:detail'])]
    #[ORM\OneToMany(targetEntity: ProjectMember::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    private Collection $members;

    #[ORM\OneToOne(targetEntity: Budget::class, mappedBy: 'project', cascade: ['persist'])]
    private Budget $budget;

    /** @var Collection<int, Stage> */
    #[ORM\OneToMany(targetEntity: Stage::class, mappedBy: 'project', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $stages;

    /** @var Collection<int, Category> */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'project', cascade: ['persist'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $categories;

    public function __construct(string $name, string $currency)
    {
        $this->name = trim($name);
        $this->currency = strtoupper($currency);
        $this->createdAt = new \DateTimeImmutable();
        $this->members = new ArrayCollection();
        $this->stages = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->budget = new Budget($this);
    }

    public function getBudget(): Budget
    {
        return $this->budget;
    }

    /**
     * @return Collection<int, Stage>
     */
    public function getStages(): Collection
    {
        return $this->stages;
    }

    public function addStage(Stage $stage): void
    {
        $this->stages->add($stage);
    }

    public function removeStage(Stage $stage): void
    {
        $this->stages->removeElement($stage);
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        $this->categories->add($category);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): void
    {
        $this->status = $status;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, ProjectMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function findMember(User $user): ?ProjectMember
    {
        foreach ($this->members as $member) {
            // Compare ids too: the entity manager may have been cleared since login.
            if ($member->getUser() === $user || (null !== $user->getId() && $member->getUser()->getId() === $user->getId())) {
                return $member;
            }
        }

        return null;
    }

    public function addMember(ProjectMember $member): void
    {
        $this->members->add($member);
    }

    public function removeMember(ProjectMember $member): void
    {
        $this->members->removeElement($member);
    }
}
