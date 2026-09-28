<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/** A disabled user cannot sign in. */
final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if ($user instanceof SecurityUser && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('account_disabled');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
