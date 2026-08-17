<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (Review::query()->exists()) {
            return;
        }

        $reviews = [
            [
                'customer_name' => 'Ayesha Raza',
                'customer_role' => 'Homeowner, Lahore',
                'rating' => 5,
                'content' => "Shine Star Marketing made finding our first home an absolute breeze. Our agent understood exactly what we were looking for and never pushed us toward something outside our budget. Three weeks after we started looking, we had the keys to our new place. Couldn't have asked for a smoother process.",
            ],
            [
                'customer_name' => 'Bilal Ahmed',
                'customer_role' => 'Property Investor',
                'rating' => 5,
                'content' => "I've bought four rental properties through this platform over the past two years. The listing details are always accurate, photos match reality, and the team is quick to answer questions. Reliable is the word I'd use.",
            ],
            [
                'customer_name' => 'Sana Tariq',
                'customer_role' => 'First-time Buyer',
                'rating' => 4,
                'content' => 'Great experience overall. The site is easy to navigate and the filters actually work the way you\'d expect. Only reason I\'m not giving five stars is the mobile experience could use a bit more polish, but the core site is solid.',
            ],
            [
                'customer_name' => 'Omar Farooq',
                'customer_role' => 'Tenant',
                'rating' => 5,
                'content' => 'Found my apartment in two days flat. Couldn\'t be happier.',
            ],
            [
                'customer_name' => 'Fatima Sheikh',
                'customer_role' => 'Real Estate Agent',
                'rating' => 5,
                'content' => "As an agent myself, I appreciate a platform that respects both buyers and sellers. The inquiry system keeps everything organized, and I no longer lose track of leads in a messy spreadsheet. This has genuinely changed how I manage my day-to-day.",
            ],
            [
                'customer_name' => 'Hassan Malik',
                'customer_role' => 'Homeowner',
                'rating' => 5,
                'content' => "We were relocating from abroad and needed to buy a house without being able to visit in person for the first two viewings. The team was patient, sent detailed video walkthroughs, and answered every single question honestly — including the ones about things that weren't perfect with the property. That honesty is rare, and it's why we trusted them with one of the biggest purchases of our lives.",
            ],
            [
                'customer_name' => 'Zainab Qureshi',
                'customer_role' => 'Property Investor',
                'rating' => 4,
                'content' => 'Good selection of commercial properties in the areas I was targeting. Would like to see more listings outside the major cities, but for what\'s available, the process from inquiry to closing was smooth.',
            ],
            [
                'customer_name' => 'Ahmed Khan',
                'customer_role' => 'First-time Buyer',
                'rating' => 5,
                'content' => "Everyone kept telling me buying a house would be stressful. It wasn't — mostly because of how clear and upfront the whole listing and inquiry process was here.",
            ],
            [
                'customer_name' => 'Mariam Yousuf',
                'customer_role' => 'Tenant',
                'rating' => 5,
                'content' => 'Super quick response to my rental inquiry, moved in within the week.',
            ],
            [
                'customer_name' => 'Usman Sattar',
                'customer_role' => 'Homeowner',
                'rating' => 4,
                'content' => 'Solid platform, transparent pricing, and the agent we worked with followed up consistently without being pushy about it. Would recommend to friends and family looking to buy in the city.',
            ],
        ];

        foreach ($reviews as $index => $review) {
            Review::create($review + [
                'order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
