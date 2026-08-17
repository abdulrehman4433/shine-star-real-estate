<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Rent', 'Sale', 'Lease', 'Commercial'] as $index => $name) {
            PropertyType::query()->firstOrCreate(
                ['name' => $name],
                ['order' => $index, 'is_active' => true],
            );
        }
    }
}
