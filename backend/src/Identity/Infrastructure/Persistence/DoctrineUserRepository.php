<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence;

use App\Identity\Application\Query\UserDirectory;
use App\Identity\Application\Query\UserQueries;
use App\Identity\Application\Query\UserView;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Error\NotFound;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepository, UserQueries, UserDirectory
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

    public function view(int $id): ?UserView
    {
        $user = $this->em->find(User::class, $id);

        return null === $user ? null : self::toView($user);
    }

    public function views(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        $views = [];
        foreach ($this->em->getRepository(User::class)->findBy(['id' => array_values(array_unique($ids))]) as $user) {
            $views[(int) $user->getId()] = self::toView($user);
        }

        return $views;
    }

    public function admins(): array
    {
        return array_map(self::toView(...), $this->em->getRepository(User::class)->findBy(['admin' => true, 'active' => true], ['fullName' => 'ASC']));
    }

    private static function toView(User $user): UserView
    {
        return new UserView((int) $user->getId(), $user->getEmail(), $user->getFullName(), $user->isAdmin(), $user->isSuperAdmin(), $user->isActive());
    }
}
