<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Application\Bus\EventPublisher;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final readonly class MessengerEventPublisher implements EventPublisher
{
    public function __construct(#[Target('event.bus')] private MessageBusInterface $bus)
    {
    }

    public function publish(object ...$events): void
    {
        foreach ($events as $event) {
            // Outside a command the stamp is ignored and the event is handled right away.
            $this->bus->dispatch(new Envelope($event, [new DispatchAfterCurrentBusStamp()]));
        }
    }
}
