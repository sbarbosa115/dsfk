<?php

declare(strict_types=1);

namespace App\Notification\Application\Port;

final readonly class Recipient
{
    public function __construct(public string $email, public string $name)
    {
    }
}
