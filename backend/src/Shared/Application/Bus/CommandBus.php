<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * Runs one command's handler inside a database transaction and returns what the handler returned. Events the
 * handler publishes are handled after the transaction commits.
 */
interface CommandBus
{
    public function dispatch(object $command): mixed;
}
