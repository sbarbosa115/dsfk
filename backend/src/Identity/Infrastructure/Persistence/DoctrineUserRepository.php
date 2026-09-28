<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Application\Query\UserQueries;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Error\NotFound;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepository, UserQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function get(int $id): User
    {
        return $this->em->find(User::class, $id) ?? throw new NotFound('user_not_found');
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => User::normalizeEmail($email)]);
    }

    public function add(User $user): void
    {
        $this->em->persist($user);
    }

    public function all(): array
    {
        return $this->em->getRepository(User::class)->findBy([], ['fullName' => 'ASC', 'id' => 'ASC']);
    }

    public function byId(int $id): ?User
    {
        return $this->em->find(User::class, $id);
    }
}
