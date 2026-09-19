<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Buffers notification emails during a request and sends them only once the request
 * succeeded (see NotificationFlushListener), so a failed action never emails anyone.
 * Sending goes through Messenger, consumed by cron on shared hosting.
 */
class Notifier
{
    /** @var list<TemplatedEmail> */
    private array $queue = [];

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(APP_URL)%')] private readonly string $appUrl,
    ) {
    }

    /**
     * @param iterable<User|null>  $recipients
     * @param array<string, mixed> $context
     */
    public function notify(iterable $recipients, string $subject, string $template, array $context = []): void
    {
        $seen = [];
        foreach ($recipients as $user) {
            if (null === $user || !$user->isActive() || isset($seen[$user->getEmail()])) {
                continue;
            }
            $seen[$user->getEmail()] = true;

            $this->queue[] = (new TemplatedEmail())
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($subject)
                ->htmlTemplate("emails/$template.html.twig")
                ->context($context + ['recipient' => $user, 'appUrl' => rtrim($this->appUrl, '/'), 'subject' => $subject]);
        }
    }

    public function flush(): void
    {
        $queue = $this->queue;
        $this->queue = [];
        foreach ($queue as $email) {
            try {
                $this->mailer->send($email);
            } catch (\Throwable $e) {
                // An email problem must never break the business action that triggered it.
                $this->logger->error('Notification email failed: '.$e->getMessage(), ['subject' => $email->getSubject()]);
            }
        }
    }

    public function discard(): void
    {
        $this->queue = [];
    }
}
