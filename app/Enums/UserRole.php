<?php

namespace App\Enums;

enum UserRole: string
{
    case Citizen = 'citizen';
    case Staff = 'staff';
    case Manager = 'manager';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citizen',
            self::Staff => 'Staff',
            self::Manager => 'Manager',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Staff => 'staff',
            self::Manager => 'manager',
            self::SuperAdmin => 'success',
            self::Citizen => 'info',
        };
    }
}
