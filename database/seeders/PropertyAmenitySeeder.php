<?php

namespace Database\Seeders;

use App\Models\PropertyAmenity;
use Illuminate\Database\Seeder;

class PropertyAmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            'Swimming Pool', 'Parking', 'Gym', 'Elevator', 'Security',
            'Garden', 'Balcony', 'Air Conditioning', 'Furnished', 'Pet Friendly',
        ];

        foreach ($amenities as $index => $name) {
            PropertyAmenity::query()->firstOrCreate(
                ['name' => $name],
                ['order' => $index, 'is_active' => true],
            );
        }
    }
}
