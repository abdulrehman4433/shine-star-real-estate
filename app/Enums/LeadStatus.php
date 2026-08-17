<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Negotiating = 'negotiating';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Negotiating => 'Negotiating',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'text-bg-secondary',
            self::Contacted => 'text-bg-info',
            self::Qualified => 'text-bg-primary',
            self::Negotiating => 'text-bg-warning',
            self::Won => 'text-bg-success',
            self::Lost => 'text-bg-danger',
        };
    }
}
