<?php

namespace App\Enums;

enum PaymentMethodEnum: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case MobileMoney = 'mobile_money';
    case Wallet = 'wallet';
}
