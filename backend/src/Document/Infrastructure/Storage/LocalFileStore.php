<?php

declare(strict_types=1);

namespace App\Document\Infrastructure\Storage;

use App\Document\Application\Port\FileStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;

/** Files under var/uploads (outside public/), one folder per project. */
final readonly class LocalFileStore implements FileStore
{
    private int $directoryMode;

    public function __construct(
        #[Autowire('%app.upload_dir%')] private string $directory,
        /* Octal, e.g. "0750" (production: only the app's user and group). */
        #[Autowire('%env(UPLOAD_DIR_MODE)%')] string $directoryMode = '0750',
        private Filesystem $filesystem = new Filesystem(),
    ) {
        $this->directoryMode = (int) octdec($directoryMode);
    }

    public function mimeTypeOf(string $path): string
    {
        return MimeTypes::getDefault()->guessMimeType($path) ?? 'application/octet-stream';
    }

    public function keep(string $path, string $storedName): void
    {
        $this->filesystem->mkdir(\dirname($this->path($storedName)), $this->directoryMode);
        $this->filesystem->rename($path, $this->path($storedName));
        $this->filesystem->chmod($this->path($storedName), 0o640);
    }

    public function locate(string $storedName): ?string
    {
        $path = $this->path($storedName);

        return is_file($path) ? $path : null;
    }

    private function path(string $storedName): string
    {
        if (!preg_match('#^\d+/[a-f0-9]{32}\.[a-z]{3,4}$#', $storedName)) {
            throw new \InvalidArgumentException('Unexpected stored name.');
        }

        return $this->directory.'/'.$storedName;
    }
}
