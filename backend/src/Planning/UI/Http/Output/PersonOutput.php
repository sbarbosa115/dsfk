<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Output;

final readonly class PersonOutput
{
    public function __construct(public int $id, public string $fullName)
    {
    }
}
