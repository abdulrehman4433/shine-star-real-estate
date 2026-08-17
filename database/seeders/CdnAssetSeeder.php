<?php

namespace Database\Seeders;

use App\Models\CdnAsset;
use Illuminate\Database\Seeder;

class CdnAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (CdnAsset::query()->exists()) {
            return;
        }

        $defaults = [
            // --- Header: CSS ---
            // Font Awesome 6 icons (loaded from CDN, not bundled via npm)
            ['location' => 'header', 'type' => 'css', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', 'order' => 0, 'is_active' => true],
            // Animate.css for scroll/entry animations
            ['location' => 'header', 'type' => 'css', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css', 'order' => 1, 'is_active' => true],

            // --- Header: Fonts ---
            // Google Fonts — Inter (modern sans-serif, loaded via stylesheet URL)
            ['location' => 'header', 'type' => 'font', 'url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', 'order' => 0, 'is_active' => true],
            // Google Fonts — Poppins (alternative, inactive to demonstrate the toggle)
            ['location' => 'header', 'type' => 'font', 'url' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', 'order' => 1, 'is_active' => false],

            // --- Header: JS (deferred) ---
            // Bootstrap 5 JS is already bundled via Vite/npm — adding a CDN copy
            // would double-load it. This inactive entry shows what a Bootstrap CDN
            // link looks like without affecting the frontend.
            ['location' => 'header', 'type' => 'js', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js', 'order' => 0, 'is_active' => false],

            // --- Footer: JS ---
            // Alpine.js (inactive — already bundled via Livewire's ESM import)
            ['location' => 'footer', 'type' => 'js', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.14.8/cdn.min.js', 'order' => 0, 'is_active' => false],
            // Lodash utility library (inactive, just as a demo entry)
            ['location' => 'footer', 'type' => 'js', 'url' => 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js', 'order' => 1, 'is_active' => false],
        ];

        foreach ($defaults as $data) {
            CdnAsset::create($data);
        }

        $this->command->info('Seeded ' . count($defaults) . ' CDN assets.');
    }
}
