<?php

declare(strict_types=1);

namespace App\Expense\UI\Http\Output;

final readonly class ExpensePermissionsOutput
{
    public function __construct(
        public bool $edit,
        public bool $attach,
        public bool $approve,
        public bool $reject,
        public bool $void,
        public bool $reimburse,
    ) {
    }
}
