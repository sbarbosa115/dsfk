<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Presenter;

use App\Identity\Application\Port\MembershipDirectory;
use App\Identity\Application\Query\UserQueries;
use App\Identity\UI\Http\Output\CurrentUserOutput;
use App\Identity\UI\Http\Output\ImpersonatorOutput;
use App\Identity\UI\Http\Output\MembershipOutput;
use App\Shared\Application\Security\Actor;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/** The signed-in user, as returned by /api/login, /api/me and /api/impersonate. */
final readonly class CurrentUserPresenter
{
    public function __construct(
        private UserQueries $users,
        private MembershipDirectory $memberships,
        #[Autowire('%env(bool:IMPERSONATION_ENABLED)%')] private bool $impersonationEnabled,
    ) {
    }

    public function present(Actor $actor, ?TokenInterface $token): CurrentUserOutput
    {
        $user = $this->users->byId($actor->getId()) ?? throw new \LogicException('The signed-in user no longer exists.');
        $original = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : null;
        $impersonator = $original instanceof Actor ? new ImpersonatorOutput($original->getId(), $original->getFullName()) : null;

        return new CurrentUserOutput(
            $actor->getId(),
            $user->getEmail(),
            $user->getFullName(),
            $user->isAdmin(),
            $user->isSuperAdmin(),
            MembershipOutput::list($this->memberships->ofUser($actor->getId())),
            $impersonator,
            $this->impersonationEnabled && ($user->isSuperAdmin() || null !== $impersonator),
        );
    }
}
