<?php

namespace Database\Seeders;

use App\Models\HeaderTemplate;
use Illuminate\Database\Seeder;

class HeaderTemplateSeeder extends Seeder
{
    /**
     * Seed a premium default header template that's immediately visible after seeding.
     *
     * Deliberately reuses HeaderTemplate::defaultHtmlTemplate()/defaultCssTemplate()/
     * defaultJsTemplate() rather than maintaining its own separate copy of the HTML/CSS/JS — the
     * two used to drift apart (this seeder and Admin\Headers\Manager::createTemplate() each had
     * their own near-duplicate heredocs), which is exactly how settings like menu_position and
     * dropdown_hover_style ended up wired into the settings form but silently never applied to the
     * rendered output. One shared source of truth for "what does a header template look like."
     */
    public function run(): void
    {
        if (HeaderTemplate::query()->exists()) {
            return;
        }

        HeaderTemplate::create([
            'name' => 'Shine Star Premium',
            'status' => 'published',
            'is_active' => true,
            'html' => HeaderTemplate::defaultHtmlTemplate(),
            'css' => HeaderTemplate::defaultCssTemplate(),
            'js' => HeaderTemplate::defaultJsTemplate(),
            'settings' => array_merge(HeaderTemplate::defaultSettings(), [
                'header_height' => 76,
                'header_border_color' => '#eef0f4',
                'menu_position' => 'left',
                'cta_login_label' => 'Sign In',
                'cta_register_label' => 'Get Started',
                'show_search' => true,
                'nav_item_padding_y' => 10,
                'logo_height' => 34,
                'logo_width' => 150,
                'shadow_color' => 'rgba(5, 45, 100, 0.06)',
            ]),
        ]);
    }
}
