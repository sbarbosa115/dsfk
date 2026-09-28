<?php

declare(strict_types=1);

namespace App\Notification\UI\Cli;

use App\Notification\Application\Service\DailyDigest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Run once a day from cron: the digest of what is pending, per active project. */
#[AsCommand(name: 'app:alerts:daily', description: 'Send the daily project digest emails.')]
final readonly class DailyDigestCommand
{
    public function __construct(private DailyDigest $digest)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(\sprintf('%d project digest(s) queued.', $this->digest->send()));

        return Command::SUCCESS;
    }
}
