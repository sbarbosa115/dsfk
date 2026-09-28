<?php

declare(strict_types=1);

namespace App\Identity\UI\Cli;

use App\Identity\Application\Command\CreateFirstSuperAdmin;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Domain\Error\DomainError;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-admin', description: 'Create a super admin (used for the first login on a new install).')]
final readonly class CreateAdminCommand
{
    public function __construct(private CommandBus $bus)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument] string $email,
        #[Argument] string $fullName,
        /* For hosts without SSH (run once from a cPanel cron job): prints a random password to change after the first login. */
        #[Option] bool $generatePassword = false,
    ): int {
        if ($generatePassword) {
            $password = rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
        } else {
            $password = $io->askHidden('Password (min. 8 characters)', static function (?string $value): string {
                if (null === $value || mb_strlen($value) < 8) {
                    throw new \RuntimeException('The password must have at least 8 characters.');
                }

                return $value;
            });
            \assert(\is_string($password));
        }

        try {
            $this->bus->dispatch(new CreateFirstSuperAdmin($email, $fullName, $password));
        } catch (DomainError $e) {
            $io->error('email_taken' === $e->errorCode ? 'A user with that email already exists.' : $e->errorCode);

            return Command::FAILURE;
        }

        $io->success(\sprintf('Super admin %s created.', mb_strtolower(trim($email))));
        if ($generatePassword) {
            $io->writeln(\sprintf('Temporary password: %s  (change it from "Cambiar contraseña" after logging in)', $password));
        }

        return Command::SUCCESS;
    }
}
