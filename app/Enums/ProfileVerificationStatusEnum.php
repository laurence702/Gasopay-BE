<?php

namespace App\Enums;

enum ProfileVerificationStatusEnum: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
} 