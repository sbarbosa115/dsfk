<?php

declare(strict_types=1);

namespace App\Shared\Application\Security;

/**
 * The signed-in person, as every context sees them. Controllers receive it with #[CurrentUser] and pass its id
 * into commands; the Identity context provides the implementation.
 */
interface Actor
{
    public function getId(): int;

    public function getFullName(): string;

    public function isAdmin(): bool;

    public function isSuperAdmin(): bool;
}
