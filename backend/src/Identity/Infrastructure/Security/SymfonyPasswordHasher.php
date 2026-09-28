<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Application\Port\PasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/** Hashes with the `auto` hasher configured for signed-in users (security.yaml). */
final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    private PasswordHasherInterface $hasher;

    public function __construct(PasswordHasherFactoryInterface $factory)
    {
        $this->hasher = $factory->getPasswordHasher(SecurityUser::class);
    }

    public function hash(string $plainPassword): string
    {
        return $this->hasher->hash($plainPassword);
    }

    public function verify(string $hash, string $plainPassword): bool
    {
        return '' !== $hash && $this->hasher->verify($hash, $plainPassword);
    }
}
