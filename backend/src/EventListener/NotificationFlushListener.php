<?php

namespace App\EventListener;

use App\Service\Notifier;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class NotificationFlushListener
{
    public function __construct(private readonly Notifier $notifier)
    {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $event->getResponse()->getStatusCode() < 400 ? $this->notifier->flush() : $this->notifier->discard();
    }

    #[AsEventListener(event: ConsoleEvents::TERMINATE)]
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        0 === $event->getExitCode() ? $this->notifier->flush() : $this->notifier->discard();
    }
}
