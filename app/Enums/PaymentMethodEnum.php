<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Wallet = 'wallet';
    case POS = 'pos';
}
