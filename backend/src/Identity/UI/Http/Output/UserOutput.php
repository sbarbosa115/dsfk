<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Output;

use App\Identity\Application\Query\Membership;
use App\Identity\Domain\Model\User;

final readonly class UserOutput
{
    /**
     * @param list<MembershipOutput> $memberships
     */
    public function __construct(
        public int $id,
        public string $email,
        public string $fullName,
        public bool $admin,
        public bool $superAdmin,
        public bool $active,
        public \DateTimeImmutable $createdAt,
        public array $memberships,
    ) {
    }

    /**
     * @param list<Membership> $memberships
     */
    public static function from(User $user, array $memberships = []): self
    {
        return new self(
            (int) $user->getId(),
            $user->getEmail(),
            $user->getFullName(),
            $user->isAdmin(),
            $user->isSuperAdmin(),
            $user->isActive(),
            $user->getCreatedAt(),
            MembershipOutput::list($memberships),
        );
    }
}
