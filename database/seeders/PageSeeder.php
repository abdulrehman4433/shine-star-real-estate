<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $page = Page::query()->firstOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'status' => 'published',
                'template' => 'default',
                'meta_title' => 'About Us - Shine Star Marketing',
                'meta_description' => 'Learn more about Shine Star Marketing, your trusted property portal.',
                'order' => 0,
            ],
        );

        if ($page->blocks()->exists()) {
            return;
        }

        $page->blocks()->createMany([
            [
                'type' => 'hero',
                'order' => 0,
                'content' => [
                    'heading' => 'About Shine Star Marketing',
                    'subheading' => 'Connecting buyers, renters, and agents since day one.',
                    'button_text' => 'Browse Properties',
                    'button_url' => '/properties',
                ],
            ],
            [
                'type' => 'text',
                'order' => 1,
                'content' => [
                    'heading' => 'Our Story',
                    'body' => "Shine Star Marketing was built to make finding and listing property simple, transparent, and fast.\n\nWe work with independent agents, agencies, and developers to bring verified listings to buyers and renters everywhere.",
                ],
            ],
            [
                'type' => 'cta',
                'order' => 2,
                'content' => [
                    'heading' => 'Ready to list your property?',
                    'button_text' => 'Get Started',
                    'button_url' => '/register',
                ],
            ],
        ]);
    }
}
