<?php

namespace App\Enums;

enum PageBlockType: string
{
    case Hero = 'hero';
    case Text = 'text';
    case Gallery = 'gallery';
    case ContactForm = 'contact-form';
    case Map = 'map';
    case Cta = 'cta';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Text => 'Text',
            self::Gallery => 'Gallery',
            self::ContactForm => 'Contact Form',
            self::Map => 'Map',
            self::Cta => 'Call to Action',
        };
    }

    /** Default JSON `content` shape for a freshly-added block of this type. */
    public function defaultContent(): array
    {
        return match ($this) {
            self::Hero => ['heading' => 'Your Heading', 'subheading' => '', 'button_text' => '', 'button_url' => ''],
            self::Text => ['heading' => '', 'body' => ''],
            self::Gallery => [],
            self::ContactForm => ['heading' => 'Contact Us', 'description' => ''],
            self::Map => ['label' => '', 'lat' => 20.0, 'lng' => 0.0, 'zoom' => 12],
            self::Cta => ['heading' => 'Ready to get started?', 'button_text' => 'Learn More', 'button_url' => ''],
        };
    }
}
