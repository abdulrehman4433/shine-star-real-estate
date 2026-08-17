<?php

namespace Database\Seeders;

use App\Models\PropertyCategory;
use Illuminate\Database\Seeder;

class PropertyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tree = [
            'Residential' => ['Apartment', 'Villa', 'House', 'Studio'],
            'Commercial' => ['Office', 'Shop', 'Warehouse'],
            'Plots & Land' => ['Residential Plot', 'Commercial Plot', 'Agricultural Land'],
        ];

        $order = 0;

        foreach ($tree as $rootName => $children) {
            $root = PropertyCategory::query()->firstOrCreate(
                ['name' => $rootName, 'parent_id' => null],
                ['order' => $order++, 'is_active' => true],
            );

            foreach ($children as $childOrder => $childName) {
                PropertyCategory::query()->firstOrCreate(
                    ['name' => $childName, 'parent_id' => $root->id],
                    ['order' => $childOrder, 'is_active' => true],
                );
            }
        }
    }
}
