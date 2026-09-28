<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Publishes domain events. Inside a command they are delivered once the command's transaction has committed, so
 * a failed command sends no email.
 */
interface EventPublisher
{
    public function publish(object ...$events): void;
}
