<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Mail;

use App\Notification\Application\Port\Outbox;
use App\Notification\Application\Port\Recipient;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Twig emails through the mailer, which queues them on Messenger (sent by the cron-driven consumer). */
final readonly class TemplatedOutbox implements Outbox
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        #[Autowire('%env(APP_URL)%')] private string $appUrl,
    ) {
    }

    public function send(array $to, string $template, array $subject, array $context): void
    {
        $subject = $this->translator->trans("$template.subject", $subject, 'emails');
        // Subjects carry people's text (an expense's description): one line, no control characters.
        $subject = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $subject));
        $seen = [];
        foreach ($to as $recipient) {
            if (!$recipient instanceof Recipient || isset($seen[strtolower($recipient->email)])) {
                continue;
            }
            $seen[strtolower($recipient->email)] = true;
            $email = (new TemplatedEmail())
                ->to(new Address($recipient->email, $recipient->name))
                ->subject($subject)
                ->htmlTemplate("emails/$template.html.twig")
                ->context($context + ['recipient' => $recipient, 'appUrl' => rtrim($this->appUrl, '/'), 'subject' => $subject]);
            try {
                $this->mailer->send($email);
            } catch (\Throwable $e) {
                // An email problem never breaks the action that caused it.
                $this->logger->error('Notification email failed: '.$e->getMessage(), ['subject' => $subject]);
            }
        }
    }
}
