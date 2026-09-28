<?php

declare(strict_types=1);

namespace App\Shared\UI\Cli;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Checks that the server can run the app: PHP and its extensions, the configuration, writable folders, the built
 * frontend and the database. The deploy script runs it after every update.
 */
#[AsCommand(name: 'app:doctor', description: 'Check that this server is correctly set up for the application.')]
final readonly class DoctorCommand
{
    private const EXTENSIONS = ['pdo_mysql', 'intl', 'mbstring', 'ctype', 'iconv', 'fileinfo', 'json', 'openssl', 'tokenizer', 'xml'];

    public function __construct(
        private Connection $connection,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        #[Autowire('%app.upload_dir%')] private string $uploadDir,
        #[Autowire('%kernel.environment%')] private string $environment,
        #[Autowire('%env(MAILER_DSN)%')] private string $mailerDsn,
        #[Autowire('%env(APP_URL)%')] private string $appUrl,
        #[Autowire('%env(APP_SECRET)%')] private string $appSecret,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        /** @var list<array{bool, string, string, bool}> $rows ok, check, detail, informative */
        $rows = [];
        $check = static function (string $label, bool $ok, string $detail = '', bool $informative = false) use (&$rows): void {
            $rows[] = [$ok, $label, $detail, $informative];
        };

        // Composer already requires PHP 8.4: the row says which version the server runs.
        $check('PHP version', true, \PHP_VERSION);
        foreach (self::EXTENSIONS as $extension) {
            $check("PHP extension $extension", \extension_loaded($extension));
        }
        $check('OPcache enabled', \function_exists('opcache_get_status') && false !== @opcache_get_status(false), 'the command line often has it off while the website has it on', true);
        $check('Environment is prod', 'prod' === $this->environment, $this->environment);
        $check('APP_SECRET set', \strlen($this->appSecret) >= 16);
        $check('APP_URL set', str_starts_with($this->appUrl, 'http') && !str_contains($this->appUrl, 'localhost'), $this->appUrl);
        $check('MAILER_DSN set', !str_starts_with($this->mailerDsn, 'null://'), self::maskDsn($this->mailerDsn));

        foreach (['var/cache', 'var/log', 'var/sessions'] as $dir) {
            $path = $this->projectDir.'/'.$dir;
            @mkdir($path, 0o775, true);
            $check("$dir writable", is_writable($path));
        }
        @mkdir($this->uploadDir, 0o750, true);
        $check('Upload folder writable', is_writable($this->uploadDir), $this->uploadDir);
        $check('Frontend built', is_file($this->projectDir.'/public/build/entrypoints.json'), 'public/build (Encore)');

        try {
            $check('Database connection', true, (string) $this->connection->fetchOne('SELECT VERSION()'));
            $tables = $this->connection->createSchemaManager()->listTableNames();
            $check('Database migrated', [] === array_diff(['budget', 'fund_movement', 'expense', 'audit_log', 'messenger_messages'], $tables), 'run doctrine:migrations:migrate if FAIL');
        } catch (\Throwable $e) {
            $check('Database connection', false, mb_substr($e->getMessage(), 0, 120));
        }

        $io->table(['', 'Check', 'Detail'], array_map(static fn (array $r): array => [$r[0] ? '<info>OK</info>' : ($r[3] ? '<comment>INFO</comment>' : '<error>FAIL</error>'), $r[1], $r[2]], $rows));
        $failed = \count(array_filter($rows, static fn (array $r): bool => !$r[0] && !$r[3]));
        if ($failed > 0) {
            $io->error("$failed check(s) failed.");

            return Command::FAILURE;
        }
        $io->success('Server ready.');

        return Command::SUCCESS;
    }

    /** "smtp://user:pass@host" → "smtp://***@host", up to the last "@" of the authority, since passwords may contain one. */
    public static function maskDsn(string $dsn): string
    {
        return (string) preg_replace('#//[^/?]*@#', '//***@', $dsn);
    }
}
