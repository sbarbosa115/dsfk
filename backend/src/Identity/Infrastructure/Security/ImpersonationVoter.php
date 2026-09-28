<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may "view as" another user (Symfony switch_user). Symfony asks with the super admin's own token, even when
 * already viewing as someone. Only when IMPERSONATION_ENABLED is on; only super admins; only active, non-admin
 * targets other than themselves.
 *
 * @extends Voter<string, SecurityUser>
 */
final class ImpersonationVoter extends Voter
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
        return self::ATTRIBUTE === $attribute && $subject instanceof SecurityUser;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $superAdmin = $token->getUser();

        return $this->enabled
            && $superAdmin instanceof SecurityUser && $superAdmin->isSuperAdmin()
            && $subject->isActive() && !$subject->isAdmin()
            && $subject->getId() !== $superAdmin->getId();
    }
}
