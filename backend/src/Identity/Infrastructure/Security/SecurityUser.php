<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Model\User;
use App\Shared\Application\Security\Actor;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The signed-in user as Symfony Security sees it: a snapshot of the domain User, reloaded on every request by
 * SecurityUserProvider. Changing the password, the roles or disabling the account ends other sessions.
 */
final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface, Actor
{
    private function __construct(
        private int $id,
        private string $email,
        private string $fullName,
        private string $password,
        private bool $admin,
        private bool $superAdmin,
        private bool $active,
    ) {
    }

    public static function fromUser(User $user): self
    {
        $id = $user->getId() ?? throw new \LogicException('Only a saved user can sign in.');

        return new self($id, $user->getEmail(), $user->getFullName(), $user->getPasswordHash(), $user->isAdmin(), $user->isSuperAdmin(), $user->isActive());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->email);

        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return match (true) {
            $this->superAdmin => ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'],
            $this->admin => ['ROLE_USER', 'ROLE_ADMIN'],
            default => ['ROLE_USER'],
        };
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self
            && $user->id === $this->id
            && $user->password === $this->password
            && $user->getRoles() === $this->getRoles()
            && $user->active === $this->active;
    }
}
