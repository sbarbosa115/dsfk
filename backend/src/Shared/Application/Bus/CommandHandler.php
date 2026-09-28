<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Marks a command handler: an invokable class whose __invoke() takes the command it handles. services.yaml
 * registers it on the command bus, so the Application layer names no framework class.
 */
interface CommandHandler
{
}
