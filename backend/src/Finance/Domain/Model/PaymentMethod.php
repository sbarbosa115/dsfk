<?php

declare(strict_types=1);

namespace App\Finance\Domain\Model;

enum PaymentMethod: string
{
    case Transfer = 'TRANSFER';
    case Cash = 'CASH';
    case Check = 'CHECK';
    case Other = 'OTHER';
}
