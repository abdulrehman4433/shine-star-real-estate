<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (BlogPost::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@shinestarmarketing.test')->first();

        if (! $admin) {
            $this->command?->warn('Skipping BlogPostSeeder: run DemoUserSeeder first.');

            return;
        }

        $marketTrends = BlogCategory::query()->where('name', 'Market Trends')->first();
        $buyingGuides = BlogCategory::query()->where('name', 'Buying Guides')->first();

        $investing = BlogTag::query()->where('name', 'Investing')->first();
        $firstTime = BlogTag::query()->where('name', 'First-Time Buyers')->first();
        $marketReport = BlogTag::query()->where('name', 'Market Report')->first();

        $posts = [
            [
                'title' => '5 Signs the Rental Market Is Heating Up',
                'category_id' => $marketTrends?->id,
                'excerpt' => 'Rental demand is climbing in several major metros — here is what buyers and investors should watch.',
                'content' => '<p>Rental demand has picked up noticeably this quarter. Vacancy rates are down, and average time-on-market for rental listings has shortened.</p><p>For investors, this is a strong signal to evaluate cash-flowing properties in high-demand neighborhoods before prices adjust further.</p>',
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'tags' => [$investing, $marketReport],
            ],
            [
                'title' => 'A First-Time Buyer\'s Guide to Making an Offer',
                'category_id' => $buyingGuides?->id,
                'excerpt' => 'Everything a first-time buyer needs to know before submitting an offer on a home.',
                'content' => '<p>Making your first offer can feel overwhelming. Start by getting pre-approved, then work with your agent to review comparable sales in the area.</p><p>Don\'t forget to budget for inspection contingencies and closing costs — they add up quickly.</p>',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'tags' => [$firstTime],
            ],
            [
                'title' => 'Draft: Upcoming Zoning Changes to Watch',
                'category_id' => $marketTrends?->id,
                'excerpt' => 'A look at proposed zoning changes that could affect property values next year.',
                'content' => '<p>This post is still being fact-checked before publishing.</p>',
                'status' => 'draft',
                'published_at' => null,
                'tags' => [],
            ],
        ];

        foreach ($posts as $data) {
            $tags = collect($data['tags'])->filter()->pluck('id')->all();
            unset($data['tags']);

            $post = BlogPost::create(array_merge($data, ['author_id' => $admin->id]));
            $post->tags()->sync($tags);
        }
    }
}
