<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Checks that the server can run the app: PHP version and extensions, writable
 * folders, database, pending migrations, mail and URL configuration.
 */
#[AsCommand(name: 'app:doctor', description: 'Check that this server is correctly set up for the application.')]
class DoctorCommand
{
    private const EXTENSIONS = ['pdo_mysql', 'intl', 'mbstring', 'ctype', 'iconv', 'fileinfo', 'json', 'openssl', 'tokenizer', 'xml'];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%app.upload_dir%')] private readonly string $uploadDir,
        #[Autowire('%kernel.environment%')] private readonly string $environment,
        #[Autowire('%env(MAILER_DSN)%')] private readonly string $mailerDsn,
        #[Autowire('%env(APP_URL)%')] private readonly string $appUrl,
        #[Autowire('%env(APP_SECRET)%')] private readonly string $appSecret,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $rows = [];
        $check = static function (string $label, bool $ok, string $detail = '') use (&$rows): void {
            $rows[] = [$ok ? '<info>OK</info>' : '<error>FAIL</error>', $label, $detail];
        };

        $check('PHP >= 8.4.1', \PHP_VERSION_ID >= 80401, \PHP_VERSION);
        foreach (self::EXTENSIONS as $extension) {
            $check("PHP extension $extension", \extension_loaded($extension));
        }
        $check('OPcache enabled (performance)', \function_exists('opcache_get_status') && false !== @opcache_get_status(false), 'informative: the command line often has it off even when the website has it on');
        $check('Environment is prod', 'prod' === $this->environment, $this->environment);
        $check('APP_SECRET set', \strlen($this->appSecret) >= 16);
        $check('APP_URL set', str_starts_with($this->appUrl, 'http') && !str_contains($this->appUrl, 'localhost'), $this->appUrl);
        $check('MAILER_DSN set', !str_starts_with($this->mailerDsn, 'null://'), preg_replace('#//[^@]*@#', '//***@', $this->mailerDsn));

        foreach (['var/cache', 'var/log', 'var/sessions'] as $dir) {
            $path = $this->projectDir.'/'.$dir;
            @mkdir($path, 0775, true);
            $check("$dir writable", is_writable($path));
        }
        @mkdir($this->uploadDir, 0775, true);
        $check('Upload folder writable', is_writable($this->uploadDir), $this->uploadDir);
        $check('Frontend built', is_file($this->projectDir.'/public/app/index.html'));

        try {
            $version = (string) $this->connection->fetchOne('SELECT VERSION()');
            $check('Database connection', true, $version);
            $tables = $this->connection->createSchemaManager()->listTableNames();
            $check('Database migrated', \in_array('budget', $tables, true) && \in_array('messenger_messages', $tables, true), 'run doctrine:migrations:migrate if FAIL');
        } catch (\Throwable $e) {
            $check('Database connection', false, mb_substr($e->getMessage(), 0, 120));
        }

        $io->table(['', 'Check', 'Detail'], $rows);
        $failed = \count(array_filter($rows, static fn ($r) => str_contains($r[0], 'FAIL') && !str_contains($r[1], 'OPcache')));
        $failed > 0 ? $io->error("$failed check(s) failed.") : $io->success('Server ready.');

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
