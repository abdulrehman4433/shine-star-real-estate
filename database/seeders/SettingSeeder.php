<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'site_name' => 'Shine Star Marketing',
            'site_logo' => null,
            'site_favicon' => null,
            'contact_email' => 'info@shinestarmarketing.test',
            'contact_phone' => '',
            'contact_address' => '',
            'currency' => 'PKR',
            'timezone' => 'UTC',
            'sms_notifications_enabled' => '0',

            // Contact form notification (Module 15)
            'contact_notify_enabled' => '1',
            'contact_notify_email' => '',

            // Dynamic SMTP settings (Module 15) — disabled by default, so a fresh/existing install keeps
            // using .env's MAIL_* variables exactly as before until an admin explicitly opts in under
            // Admin → Settings → SMTP. smtp_password is deliberately NOT seeded here — it's stored
            // encrypted via Setting::setEncrypted(), never as a plain default value.
            'smtp_enabled' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => '',
            'smtp_from_name' => '',

            // SEO defaults (Module 10)
            'seo_default_meta_title' => 'Shine Star Marketing - Find Your Next Property',
            'seo_default_meta_description' => 'Browse verified property listings for sale and rent, powered by Shine Star Marketing.',
            'seo_robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /agent\nDisallow: /account\n\nSitemap: ".rtrim(config('app.url'), '/')."/sitemap.xml",
            'seo_ga_measurement_id' => '',
            'seo_gsc_verification' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
