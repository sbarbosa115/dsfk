<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

final readonly class ChangeOwnPassword
{
    public function __construct(public int $userId, public string $currentPassword, public string $newPassword)
    {
    }
}
