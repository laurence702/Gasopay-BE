<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Rider = 'rider';
    case Regular = 'regular';
    case SuperAdmin = 'super_admin';
}
