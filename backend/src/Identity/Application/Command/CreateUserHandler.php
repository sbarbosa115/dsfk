<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\Port\PasswordHasher;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Application\Bus\CommandHandler;
use App\Shared\Application\Bus\NewId;
use App\Shared\Domain\Error\InvalidValue;
use Psr\Clock\ClockInterface;

final readonly class CreateUserHandler implements CommandHandler
{
    public function __construct(private UserRepository $users, private PasswordHasher $hasher, private ClockInterface $clock)
    {
    }

    public function __invoke(CreateUser $command): NewId
    {
        if (null !== $this->users->findByEmail($command->email)) {
            throw new InvalidValue('email_taken');
        }

        $user = new User($command->email, $command->fullName, $this->hasher->hash($command->password), $this->clock->now());
        $user->changeAccess($this->users->get($command->actorId), admin: $command->admin, superAdmin: $command->superAdmin);
        $this->users->add($user);

        return NewId::of($user);
    }
}
