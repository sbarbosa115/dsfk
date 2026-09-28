<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

/** Where project money sits. Stage accounts are told apart by the entry's stage. */
enum LedgerAccount: string
{
    case Stage = 'STAGE';
    case PettyCash = 'PETTY_CASH';
    case Contingency = 'CONTINGENCY';
}
