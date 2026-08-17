<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw(['name' => 'Test User']),
        );

        $this->call([
            SettingSeeder::class,
            RoleSeeder::class,
            DemoUserSeeder::class,
            PropertyTypeSeeder::class,
            PropertyCategorySeeder::class,
            PropertyAmenitySeeder::class,
            PropertySeeder::class,
            FeaturedPropertyDemoSeeder::class,
            LeadSeeder::class,
            ChatSeeder::class,
            PageSeeder::class,
            ContactPageSeeder::class,
            BlogCategorySeeder::class,
            BlogTagSeeder::class,
            BlogPostSeeder::class,
            BlogPostDemoSeeder::class,
            MenuSeeder::class,
            HeaderTemplateSeeder::class,
            PageTemplateSeeder::class,
            FooterWidgetSeeder::class,
            CdnAssetSeeder::class,
            ReviewSeeder::class,
            ProjectSeeder::class,
            RealProjectSeeder::class,
        ]);
    }
}
