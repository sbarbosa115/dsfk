<?php

namespace App\Entity;

use App\Enum\ProjectRole;
use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProjectMemberRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_project_member', fields: ['project', 'user'])]
class ProjectMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['member:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['member:read'])]
    private User $user;

    #[ORM\Column(length: 30, enumType: ProjectRole::class)]
    #[Groups(['member:read'])]
    private ProjectRole $role;

    public function __construct(Project $project, User $user, ProjectRole $role)
    {
        $this->project = $project;
        $this->user = $user;
        $this->role = $role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): ProjectRole
    {
        return $this->role;
    }

    public function setRole(ProjectRole $role): void
    {
        $this->role = $role;
    }
}
