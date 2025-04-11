<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Completed = 'completed';
    case Paid = 'paid';
}
