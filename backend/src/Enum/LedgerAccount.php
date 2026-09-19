<?php

namespace App\Enum;

/**
 * Where project money sits. Stage accounts are identified by the entry's stage.
 */
enum LedgerAccount: string
{
    case Stage = 'STAGE';
    case PettyCash = 'PETTY_CASH';
    case Contingency = 'CONTINGENCY';
}
