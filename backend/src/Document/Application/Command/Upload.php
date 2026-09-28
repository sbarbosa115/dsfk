<?php

declare(strict_types=1);

namespace App\Document\Application\Command;

/** A file the request brought, already on disk at a temporary path. */
final readonly class Upload
{
    public function __construct(public string $path, public string $originalName, public int $size)
    {
    }
}
