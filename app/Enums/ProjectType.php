<?php

namespace App\Enums;

enum ProjectType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case MixedUse = 'mixed_use';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::MixedUse => 'Mixed Use',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Residential => 'text-bg-primary',
            self::Commercial => 'text-bg-info',
            self::MixedUse => 'text-bg-secondary',
        };
    }
}
