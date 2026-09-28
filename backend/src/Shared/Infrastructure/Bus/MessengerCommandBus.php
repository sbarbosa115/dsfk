<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\DelayedMessageHandlingException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class MessengerCommandBus implements CommandBus
{
    public function __construct(
        #[Target('command.bus')] private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        try {
            $envelope = $this->bus->dispatch($command);
        } catch (HandlerFailedException $e) {
            // Rethrow the handler's own error (a DomainError, usually), not Messenger's wrapper.
            throw current($e->getWrappedExceptions()) ?: $e;
        } catch (DelayedMessageHandlingException $e) {
            // The command committed; an event handler that ran afterwards failed. The write stands, so the
            // caller gets its result and the failure goes to the log.
            $this->logger->error('An event handler failed after {command} committed.', [
                'command' => $command::class,
                'exception' => $e,
            ]);
            $envelope = $e->getEnvelope();
            if (!$envelope instanceof Envelope) {
                return null;
            }
        }

        return $envelope->last(HandledStamp::class)?->getResult();
    }
}
