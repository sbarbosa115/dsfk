<?php

namespace App\Service;

use App\Entity\ProjectMember;
use App\Entity\User;
use App\Repository\ProjectMemberRepository;

/**
 * Shape of the logged-in user returned by /api/me and /api/login.
 */
class CurrentUserPresenter
{
    public function __construct(private readonly ProjectMemberRepository $members)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'admin' => $user->isAdmin(),
            'memberships' => array_map(static fn (ProjectMember $m) => [
                'projectId' => $m->getProject()->getId(),
                'projectName' => $m->getProject()->getName(),
                'role' => $m->getRole()->value,
            ], $this->members->findBy(['user' => $user])),
        ];
    }
}
