<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Models\PropertyCategory;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = Menu::query()->firstOrCreate(['name' => 'Main Menu', 'location' => 'header']);

        if ($menu->allItems()->exists()) {
            return;
        }

        $menu->allItems()->create(['label' => 'Home', 'url' => '/', 'order' => 0]);
        $properties = $menu->allItems()->create(['label' => 'Properties', 'url' => '/properties', 'order' => 1]);

        $residential = PropertyCategory::query()->where('name', 'Residential')->first();
        $commercial = PropertyCategory::query()->where('name', 'Commercial')->first();

        if ($residential) {
            $menu->allItems()->create([
                'parent_id' => $properties->id,
                'label' => 'Residential',
                'url' => '/properties?category='.$residential->id,
                'order' => 0,
            ]);
        }

        if ($commercial) {
            $menu->allItems()->create([
                'parent_id' => $properties->id,
                'label' => 'Commercial',
                'url' => '/properties?category='.$commercial->id,
                'order' => 1,
            ]);
        }

        $aboutPage = Page::query()->where('slug', 'about-us')->first();

        if ($aboutPage) {
            $menu->allItems()->create(['label' => 'About Us', 'page_id' => $aboutPage->id, 'order' => 2]);
        }

        $menu->allItems()->create(['label' => 'Blog', 'url' => '/blog', 'order' => 3]);
        $menu->allItems()->create(['label' => 'Contact', 'url' => '/contact', 'order' => 4]);
    }
}
