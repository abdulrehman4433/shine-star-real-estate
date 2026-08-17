<?php

namespace Database\Seeders;

use App\Models\BlogTag;
use Illuminate\Database\Seeder;

class BlogTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Investing', 'First-Time Buyers', 'Rentals', 'Market Report'] as $name) {
            BlogTag::query()->firstOrCreate(['name' => $name]);
        }
    }
}
