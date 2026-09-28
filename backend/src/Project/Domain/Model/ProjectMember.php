<?php

declare(strict_types=1);

namespace App\Project\Domain\Model;

use App\Shared\Domain\Model\References;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_member')]
#[ORM\UniqueConstraint(name: 'uniq_project_member', fields: ['project', 'userId'])]
class ProjectMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'members')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Project $project,
        #[ORM\Column(name: 'user_id')]
        #[References('user')]
        private int $userId,
        #[ORM\Column(length: 30, enumType: ProjectRole::class)]
        private ProjectRole $role,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRole(): ProjectRole
    {
        return $this->role;
    }

    public function changeRole(ProjectRole $role): void
    {
        $this->role = $role;
    }
}
