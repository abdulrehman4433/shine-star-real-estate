<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/** Creates the /contact-us page — nothing previously seeded this slug, so the URL 404'd via the catch-all
 *  CMS route despite being linked from the header menu/footer/several page templates' CTAs. Uses the
 *  existing 'hero' + 'text' + 'contact-form' block types (Module 7), same idempotent
 *  firstOrCreate()-keyed-on-slug + "skip if it already has blocks" pattern as PageSeeder's about-us page. */
class ContactPageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::query()->firstOrCreate(
            ['slug' => 'contact-us'],
            [
                'title' => 'Contact Us',
                'status' => 'published',
                'template' => 'default',
                'meta_title' => 'Contact Us - Shine Star Marketing',
                'meta_description' => 'Get in touch with Shine Star Marketing — questions, support, or property inquiries.',
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
                    'heading' => 'Get In Touch',
                    'subheading' => "Questions about a listing, a project, or your account? Send us a message and we'll get back to you.",
                    'button_text' => '',
                    'button_url' => '',
                ],
            ],
            [
                'type' => 'contact-form',
                'order' => 1,
                'content' => [
                    'heading' => 'Send Us a Message',
                    'description' => '',
                ],
            ],
        ]);
    }
}
