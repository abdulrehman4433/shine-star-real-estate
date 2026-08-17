<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Market Trends', 'Buying Guides', 'Agent Tips'] as $index => $name) {
            BlogCategory::query()->firstOrCreate(
                ['name' => $name],
                ['order' => $index, 'is_active' => true],
            );
        }
    }
}
