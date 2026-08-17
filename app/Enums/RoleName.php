<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Agent = 'agent';
    case Agency = 'agency';
    case User = 'user';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin/Staff',
            self::Agent => 'Agent/Developer',
            self::Agency => 'Agency',
            self::User => 'User',
            self::Guest => 'Guest User',
        };
    }

    /** Roles a visitor may pick when self-registering. */
    public static function registrable(): array
    {
        return [self::Guest, self::User, self::Agent, self::Agency];
    }
}
