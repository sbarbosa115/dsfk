<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Domain\Error\InvalidValue;

final readonly class UpdateUserHandler implements CommandHandler
{
    public function __construct(private UserRepository $users, private PasswordHasher $hasher)
    {
    }

    public function __invoke(UpdateUser $command): void
    {
        $user = $this->users->get($command->userId);
        $actor = $this->users->get($command->actorId);
        $user->assertEditableBy($actor);

        if (null !== $command->email && User::normalizeEmail($command->email) !== $user->getEmail()) {
            if (null !== $this->users->findByEmail($command->email)) {
                throw new InvalidValue('email_taken');
            }
            $user->changeEmail($command->email);
        }
        if (null !== $command->fullName) {
            $user->rename($command->fullName);
        }
        if (null !== $command->password) {
            $user->changePassword($this->hasher->hash($command->password));
        }
        $user->changeAccess($actor, $command->admin, $command->superAdmin, $command->active);
    }
}
