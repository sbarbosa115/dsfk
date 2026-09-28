<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Output;

final readonly class MemberUserOutput
{
    public function __construct(public int $id, public string $email, public string $fullName)
    {
    }
}
