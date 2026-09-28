<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Output;

final readonly class CurrentUserOutput
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
        public array $memberships,
        /** Set while a super admin is viewing the app as this user. */
        public ?ImpersonatorOutput $impersonator,
        /** Shows the "Ver como" menu: a super admin, or someone being viewed as (to offer the way back). */
        public bool $canImpersonate,
    ) {
    }
}
