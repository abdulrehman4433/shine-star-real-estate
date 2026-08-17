<?php

namespace App\Enums;

enum FooterWidgetType: string
{
    case Text = 'text';
    case Links = 'links';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Links => 'Links',
        };
    }
}
