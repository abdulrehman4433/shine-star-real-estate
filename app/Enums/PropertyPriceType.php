<?php

namespace App\Enums;

enum PropertyPriceType: string
{
    case Fixed = 'fixed';
    case Negotiable = 'negotiable';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::Negotiable => 'Negotiable',
        };
    }
}
