<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 7 additional published posts so /blog's card grid (3 columns on desktop) has enough real
 * content to fill out — BlogPostSeeder alone leaves only 2 published rows. Separate seeder on
 * purpose, same reasoning as FeaturedPropertyDemoSeeder/ProjectSeeder: "give the page something
 * to show" rather than folding more rows into the seeder that demonstrates draft/published status.
 */
class BlogPostDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (BlogPost::where('title', 'How to Choose the Right Neighborhood Before You Buy')->exists()) {
            return;
        }

        $author = User::query()->where('email', 'admin@shinestarmarketing.test')->first();

        if (! $author) {
            $this->command?->warn('Skipping BlogPostDemoSeeder: demo admin account not found. Run DemoUserSeeder first.');

            return;
        }

        $categories = BlogCategory::query()->pluck('id', 'name');
        $tags = BlogTag::query()->pluck('id', 'name');

        if ($categories->isEmpty()) {
            $this->command?->warn('Skipping BlogPostDemoSeeder: run BlogCategorySeeder first.');

            return;
        }

        $posts = [
            [
                'title' => 'How to Choose the Right Neighborhood Before You Buy',
                'category' => 'Buying Guides',
                'tags' => ['First-Time Buyers'],
                'excerpt' => 'Price and square footage matter, but the neighborhood is what you actually live in every day. Here\'s what to check before you commit.',
                'content' => "Most buyers spend weeks comparing floor plans and almost no time on the street outside. That's backwards — you can renovate a kitchen, but you can't renovate a commute or a school catchment.\n\nStart by visiting at different times of day. A neighborhood that feels quiet on a Sunday afternoon viewing can be a different place during a weekday rush hour. Walk to the nearest market, check how long it actually takes, not how long Google Maps says it takes.\n\nTalk to a few neighbors if you get the chance. Ask about noise, flooding during monsoon season, and how responsive the local management or society office is when something goes wrong. These are the details that never show up in a listing.\n\nFinally, look at what's being built nearby, not just what's already there. A new commercial plaza a block away can be a huge convenience — or a source of years of construction noise. Ask the agent, and if they don't know, ask the local development authority directly.",
            ],
            [
                'title' => 'Renting vs. Buying: Doing the Real Math for 2026',
                'category' => 'Market Trends',
                'tags' => ['Investing', 'Market Report'],
                'excerpt' => 'The "rent is throwing money away" advice ignores half the numbers. Here\'s a more honest way to compare the two.',
                'content' => "The traditional argument for buying assumes property values only go up and ignores maintenance, taxes, and the opportunity cost of a large down payment sitting in a house instead of anywhere else.\n\nA fairer comparison looks at your total monthly cost of ownership — installment, maintenance charges, property tax, and a reasonable estimate for repairs — against market rent for a comparable unit. If ownership costs significantly more per month than renting the same space, buying only wins if you're confident you'll stay long enough for appreciation to close that gap.\n\nThat said, buying does something renting can't: it locks in your housing cost against future rent increases, and every installment builds equity instead of disappearing. If you plan to stay in one city for five-plus years and have the down payment without stretching your finances thin, the math usually tips toward buying.\n\nThe honest answer is that there's no universal right choice — just a right choice for your specific timeline and finances.",
            ],
            [
                'title' => 'A First-Time Buyer\'s Guide to Booking a Plot in a New Society',
                'category' => 'Buying Guides',
                'tags' => ['First-Time Buyers', 'Investing'],
                'excerpt' => 'Booking amounts, confirmation payments, installment plans — the vocabulary alone can be intimidating. Here\'s what each term actually means.',
                'content' => "Buying a developed house is straightforward: you agree on a price, you pay it, you get the keys. Booking a plot in a society that's still being developed works differently, and the terminology trips up a lot of first-time buyers.\n\nThe booking amount is your initial payment to reserve a specific plot size and location — think of it as a deposit that secures your spot in the queue. The confirmation amount usually follows once the developer allots you an actual plot number, converting your booking into a real, numbered unit.\n\nAfter that, most societies offer an installment plan — a fixed monthly or quarterly payment over a set number of years, ending with a possession charge due once development work in your block is complete and you're handed the keys.\n\nBefore booking anywhere, ask for the payment schedule in writing, confirm what happens if the development is delayed, and — if at all possible — visit the site in person rather than relying on renderings alone.",
            ],
            [
                'title' => 'Five Small Upgrades That Actually Raise a Property\'s Resale Value',
                'category' => 'Market Trends',
                'tags' => ['Investing'],
                'excerpt' => 'Not every renovation pays for itself when you sell. These five consistently do.',
                'content' => "A full kitchen remodel rarely returns what you spent on it, but a handful of smaller changes reliably make a property show and sell better.\n\nFresh, neutral paint throughout is the cheapest, highest-return change available — it makes every room look larger and better maintained in photos and in person. A deep clean and repair of the main entrance and boundary wall matters more than people expect, since it's the very first impression a buyer forms.\n\nFixing visible water damage or damp patches before listing, rather than after a buyer's inspection flags them, avoids giving anyone leverage to negotiate the price down. Updating old light fixtures and switches is inexpensive and noticeably changes how a space feels. And finally, decluttering and depersonalizing before photos are taken makes rooms look bigger and lets a buyer picture their own life there.\n\nNone of these require a big budget — they just require doing them before the property goes live on a listing site, not after the first round of viewings.",
            ],
            [
                'title' => 'Understanding Rental Agreements: What Every Tenant Should Read Twice',
                'category' => 'Agent Tips',
                'tags' => ['Rentals'],
                'excerpt' => 'Most disputes between tenants and landlords trace back to a clause nobody actually read before signing.',
                'content' => "A rental agreement is the single most important document in a tenancy, and it's also the one most people skim through fastest.\n\nPay close attention to the maintenance clause — does it specify who's responsible for what, and are there caps on cost-sharing for major repairs? Check the notice period required to end the tenancy on both sides, since a mismatch here is one of the most common sources of disputes.\n\nLook for how the security deposit is handled: the amount, the conditions for deductions, and the timeline for returning it after move-out. If the agreement is vague on any of these, ask for it to be spelled out before signing, not after a disagreement.\n\nFinally, get everything in writing — verbal promises about rent increases, included utilities, or furniture aren't enforceable if they're not in the actual document.",
            ],
            [
                'title' => 'What Rising Construction Costs Mean for New Project Pricing',
                'category' => 'Market Trends',
                'tags' => ['Market Report', 'Investing'],
                'excerpt' => 'Material and labor costs have shifted a lot in the past year. Here\'s how that\'s showing up in new development pricing.',
                'content' => "Anyone tracking new housing developments has likely noticed prices climbing faster than they did a few years ago, and construction cost is a big part of the reason.\n\nSteel, cement, and skilled labor costs have all moved upward, and developers pass that directly into per-square-foot pricing for new bookings. Projects launched earlier, before these increases, often carry noticeably lower prices for buyers who got in during the initial booking phase — which is part of why early-bird pricing exists in the first place.\n\nFor buyers, this means the gap between an early booking price and a later-phase price in the same project can be substantial, and it's worth asking a developer directly what phase pricing looks like before assuming all units in a society cost the same.\n\nFor investors, it also means a project's early phases can offer better upside if the development is completed on schedule and demand holds — though that comes with the usual risk of delayed possession that any pre-launch booking carries.",
            ],
            [
                'title' => 'Commercial vs. Residential Investment: Which Fits Your Goals?',
                'category' => 'Agent Tips',
                'tags' => ['Investing'],
                'excerpt' => 'Both can be good investments — they just reward very different strategies and timelines.',
                'content' => "Residential and commercial property aren't just different price points — they behave differently as investments, and picking the wrong one for your goals is a common mistake.\n\nResidential property tends to be easier to finance, easier to sell later since the buyer pool is larger, and more forgiving if you need to exit quickly. Rental yields are usually modest, but demand is steady and vacancies tend to be shorter.\n\nCommercial property — shops, offices, warehouses — often offers higher rental yields and longer lease terms with tenants who have more incentive to maintain the space themselves. The tradeoff is a smaller pool of buyers when you want to sell, more sensitivity to the local business cycle, and financing that's usually harder to arrange.\n\nIf your priority is steady, lower-effort income and an easier eventual exit, residential is usually the safer starting point. If you're comfortable with a longer hold and want higher yield, commercial can outperform — as long as you've done real diligence on the tenant and the local commercial demand, not just the building itself.",
            ],
        ];

        foreach ($posts as $index => $data) {
            $post = BlogPost::create([
                'category_id' => $categories[$data['category']] ?? null,
                'author_id' => $author->id,
                'title' => $data['title'],
                'excerpt' => $data['excerpt'],
                'content' => '<p>'.str_replace("\n\n", '</p><p>', $data['content']).'</p>',
                'status' => 'published',
                'published_at' => now()->subDays(($index + 1) * 3),
            ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name] ?? null)->filter()->values();
            $post->tags()->sync($tagIds);
        }
    }
}
