<?php

declare(strict_types=1);

namespace App\Project\UI\Http\Output;

use App\Project\Domain\Model\ProjectRole;

final readonly class MemberOutput
{
    public function __construct(public int $id, public ProjectRole $role, public MemberUserOutput $user)
    {
    }
}
