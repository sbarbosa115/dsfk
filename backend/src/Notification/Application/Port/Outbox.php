<?php

declare(strict_types=1);

namespace App\Notification\Application\Port;

/**
 * Queues an email per recipient (each address once; nulls skipped), rendered from `emails/<template>.html.twig`,
 * with the subject "<template>.subject" of the emails translations. Sending goes through the queue, and a mail
 * problem never breaks the action that caused it.
 */
interface Outbox
{
    /**
     * @param list<Recipient|null>            $to
     * @param array<string, string|int|float> $subject the subject's parameters, e.g. ['project' => 'Torre Norte']
     * @param array<string, mixed>            $context
     */
    public function send(array $to, string $template, array $subject, array $context): void;
}
