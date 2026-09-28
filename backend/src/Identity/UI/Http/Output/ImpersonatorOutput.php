<?php

declare(strict_types=1);

namespace App\Identity\UI\Http\Output;

/** The super admin who is really acting while viewing the app as someone else. */
final readonly class ImpersonatorOutput
{
    public function __construct(public int $id, public string $fullName)
    {
    }
}
