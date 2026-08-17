<?php

namespace Database\Seeders;

use App\Models\FooterSetting;
use App\Models\FooterWidget;
use Illuminate\Database\Seeder;

class FooterWidgetSeeder extends Seeder
{
    public function run(): void
    {
        if (FooterWidget::query()->exists()) {
            return;
        }

        // Create or update footer-level settings
        FooterSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Main Footer',
                'status' => 'active',
                'columns' => 3,
                'company_name' => 'Shine Star Marketing',
                'copyright_text' => '&copy; ' . date('Y') . ' Shine Star Marketing. All rights reserved.',
                'copyright_tagline' => 'Find your next home, plot, or commercial space.',
                // Plain PHP array — FooterSetting casts `social_links` as 'array', which already
                // JSON-encodes on save. Pre-encoding it here (the old code did `json_encode([...])`)
                // double-encodes the column into a JSON string containing escaped JSON text, which
                // then fails to decode back into an array (see the 2026-08-14 fix note in module-08's doc).
                'social_links' => [
                    ['platform' => 'facebook', 'url' => '#', 'icon' => 'bi bi-facebook'],
                    ['platform' => 'instagram', 'url' => '#', 'icon' => 'bi bi-instagram'],
                    ['platform' => 'linkedin', 'url' => '#', 'icon' => 'bi bi-linkedin'],
                ],
                'show_company_name' => true,
                'show_tagline' => true,
                'show_social_links' => true,
                'show_copyright' => true,
            ]
        );

        // Column 0 — About / Brand
        FooterWidget::create([
            'title' => 'About Us',
            'type' => 'text',
            'content' => ['body' => 'Shine Star Marketing helps you find the perfect property — whether it\'s a home, a plot, or a commercial space in your favorite community.'],
            'column' => 0,
            'order' => 0,
            'is_active' => true,
        ]);

        // Column 1 — Quick Links
        FooterWidget::create([
            'title' => 'Quick Links',
            'type' => 'links',
            'content' => [
                'links' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'Properties', 'url' => '/properties'],
                    ['label' => 'Blog', 'url' => '/blog'],
                ],
            ],
            'column' => 1,
            'order' => 0,
            'is_active' => true,
        ]);

        // Column 2 — Contact
        FooterWidget::create([
            'title' => 'Contact',
            'type' => 'text',
            'content' => ['body' => "Email: info@shinestarmarketing.com\nPhone: (555) 123-4567\n123 Main Street, City"],
            'column' => 2,
            'order' => 0,
            'is_active' => true,
        ]);
    }
}
