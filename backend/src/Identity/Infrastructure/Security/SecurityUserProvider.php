<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Error\NotFound;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByEmail($identifier);
        if (null === $user) {
            $e = new UserNotFoundException();
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return SecurityUser::fromUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Unsupported user %s.', $user::class));
        }

        try {
            return SecurityUser::fromUser($this->users->get($user->getId()));
        } catch (NotFound) {
            throw new UserNotFoundException();
        }
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
