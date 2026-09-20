<?php

namespace App\Service;

use App\Entity\ProjectMember;
use App\Entity\User;
use App\Repository\ProjectMemberRepository;
use App\Security\ImpersonationVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

/**
 * Shape of the logged-in user returned by /api/me and /api/login.
 */
class CurrentUserPresenter
{
    public function __construct(
        private readonly ProjectMemberRepository $members,
        private readonly Security $security,
        private readonly ImpersonationVoter $impersonation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(User $user): array
    {
        $token = $this->security->getToken();
        $original = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : null;

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'admin' => $user->isAdmin(),
            'superAdmin' => $user->isSuperAdmin(),
            'memberships' => array_map(static fn (ProjectMember $m) => [
                'projectId' => $m->getProject()->getId(),
                'projectName' => $m->getProject()->getName(),
                'role' => $m->getRole()->value,
            ], $this->members->findBy(['user' => $user])),
            // Set while an admin is viewing the app as this user.
            'impersonator' => $original instanceof User ? ['id' => $original->getId(), 'fullName' => $original->getFullName()] : null,
            'canImpersonate' => $this->impersonation->isEnabled() && ($user->isSuperAdmin() || null !== $original),
        ];
    }
}
