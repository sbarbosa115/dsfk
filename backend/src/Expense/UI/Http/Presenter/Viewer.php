<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Presenter;

/** Who is looking, in the project: a manager (PM or Admin) sees everything; a Team Lead their own expenses. */
final readonly class Viewer
{
    public function __construct(public int $userId, public bool $manager, public bool $admin)
    {
    }
}
