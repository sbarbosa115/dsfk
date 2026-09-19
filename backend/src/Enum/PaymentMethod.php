<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case Transfer = 'TRANSFER';
    case Cash = 'CASH';
    case Check = 'CHECK';
    case Other = 'OTHER';
}
