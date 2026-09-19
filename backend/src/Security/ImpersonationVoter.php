<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may "view as" another user (Symfony switch_user). Symfony asks with the admin's own
 * token, even when already impersonating someone. Only when IMPERSONATION_ENABLED is on
 * (dev/test by default); only admins; only active, non-admin targets.
 *
 * @extends Voter<string, User>
 */
class ImpersonationVoter extends Voter
{
    public const ATTRIBUTE = 'CAN_SWITCH_USER';

    public function __construct(#[Autowire('%env(bool:IMPERSONATION_ENABLED)%')] private readonly bool $enabled)
    {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ATTRIBUTE === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $admin = $token->getUser();

        return $this->enabled
            && $admin instanceof User && $admin->isAdmin()
            && $subject->isActive() && !$subject->isAdmin()
            && $subject->getId() !== $admin->getId();
    }
}
