<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user (used for the first login on a new install).')]
class CreateAdminCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument] string $email,
        #[Argument] string $fullName,
        /** For hosts without SSH (run once from a cPanel cron job): prints a random password to change after the first login. */
        #[Option] bool $generatePassword = false,
    ): int {
        if (null !== $this->users->findOneBy(['email' => mb_strtolower($email)])) {
            $io->error('A user with that email already exists.');

            return Command::FAILURE;
        }

        if ($generatePassword) {
            $password = rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
        } else {
            $password = $io->askHidden('Password (min. 8 characters)', static function (?string $value): string {
                if (null === $value || mb_strlen($value) < 8) {
                    throw new \RuntimeException('The password must have at least 8 characters.');
                }

                return $value;
            });
        }

        $user = new User($email, $fullName);
        // The first admin of an install is a super admin: only a super admin can grant that
        // level to anyone else, so without this nobody could ever hand it out.
        $user->setSuperAdmin(true);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('Super admin %s created.', $email));
        if ($generatePassword) {
            $io->writeln(\sprintf('Temporary password: %s  (change it from "Cambiar contraseña" after logging in)', $password));
        }

        return Command::SUCCESS;
    }
}
