<?php

namespace App\Enums;

enum PaymentTypeEnum: string
{
    case Full = 'full';
    case Part = 'part';
}
