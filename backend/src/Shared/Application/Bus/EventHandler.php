<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Marks an event handler: an invokable class whose __invoke() takes the event it handles. services.yaml
 * registers it on the event bus, so the Application layer names no framework class.
 */
interface EventHandler
{
}
