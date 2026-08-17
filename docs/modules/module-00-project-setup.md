# Module 0: Project Setup & Foundation

**Status:** Done. **Acceptance:** app boots; both layouts render with dummy content. ✅

## What was built

- Fresh Laravel 11 project at `C:\xampp\htdocs\shine-star-marketing`, MySQL DB `shine_star_marketing`
  (root/no password, local XAMPP MySQL).
- Installed: `spatie/laravel-permission`, `spatie/laravel-medialibrary` (needed `exif` PHP extension
  enabled — see root README), `spatie/laravel-sluggable`, `spatie/laravel-sitemap`, `intervention/image`,
  `livewire/livewire` (v4), Bootstrap 5 + Alpine.js via npm (Alpine later replaced — see Module 2 doc note
  on the Livewire+Alpine bundling fix).
- Folder convention established: `app/Http/Controllers/{Admin,Frontend}`, `app/Livewire/{Admin,Frontend}`,
  `resources/views/{admin,frontend}` for plain Blade, plus `resources/views/livewire/...` for Livewire
  component views (Livewire's own default).
- Base layouts: `resources/views/admin/layouts/app.blade.php` (sidebar + topbar,
  `resources/views/admin/partials/{sidebar,topbar}.blade.php`) and
  `resources/views/frontend/layouts/app.blade.php` (header + footer,
  `resources/views/frontend/partials/{header,footer}.blade.php`).
- `settings` key-value table + `App\Models\Setting` (static `get()`/`set()` helpers, `Cache::rememberForever`
  backed, cache cleared on `set()`). Seeded via `SettingSeeder` (site_name, contact info, currency, timezone).
- `.env` / `.env.example` prepared with placeholders for Aiven/Render deploy, Mailtrap/Brevo, Cloudinary,
  Reverb (filled in properly as later modules needed them).

## Routes at end of this module

- `GET /` → `Frontend\HomeController@index` (dummy content)
- `GET /admin` → `Admin\DashboardController@index` (dummy content)

## Notes for future changes

- If you need to add a new top-level settings key, add it to `SettingSeeder` and read via
  `Setting::get('key', $default)`.
- Bootstrap 5 + Alpine assets are bundled through Vite (`resources/css/app.css`, `resources/js/app.js`) —
  Tailwind (Laravel's default scaffold) was removed entirely in favor of Bootstrap.
