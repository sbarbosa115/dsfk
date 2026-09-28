<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class ChangeOwnPasswordHandler implements CommandHandler
{
    public function __construct(private UserRepository $users, private PasswordHasher $hasher)
    {
    }

    public function __invoke(ChangeOwnPassword $command): void
    {
        $user = $this->users->get($command->userId);
        if (!$this->hasher->verify($user->getPasswordHash(), $command->currentPassword)) {
            throw InvalidValue::field('currentPassword', 'La contraseña actual no es correcta.');
        }

        $user->changePassword($this->hasher->hash($command->newPassword));
    }
}
