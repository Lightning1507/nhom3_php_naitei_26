<?php

namespace App\Enums;

enum UserRole: string
{
    case Citizen = 'citizen';
    case Staff = 'staff';
    case Manager = 'manager';
    case SuperAdmin = 'super_admin';
}
