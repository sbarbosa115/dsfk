<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Event handlers run after the command committed: one that fails (an email that cannot be built, a lookup that
 * throws) is logged, and the person's action still answers as the success it was.
 */
final readonly class ContainEventFailures implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $e) {
            $this->logger->error('Event handler failed: '.$e->getMessage(), ['event' => $envelope->getMessage()::class, 'exception' => $e]);

            return $envelope;
        }
    }
}
