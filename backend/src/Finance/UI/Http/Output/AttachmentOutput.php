<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Output;

final readonly class AttachmentOutput
{
    public function __construct(public int $id, public string $name, public string $mimeType, public int $size)
    {
    }
}
