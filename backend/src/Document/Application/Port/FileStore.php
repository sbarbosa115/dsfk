<?php

declare(strict_types=1);

namespace App\Document\Application\Port;

/** Where uploaded files are kept: a private directory, never the web root. */
interface FileStore
{
    /** The file's type from its content (not from its name or what the browser claimed). */
    public function mimeTypeOf(string $path): string;

    /** Moves an uploaded file (at its temporary path) to its stored name. */
    public function keep(string $path, string $storedName): void;

    /** Full path of a stored file; null when it is missing. */
    public function locate(string $storedName): ?string;
}
