<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * A live checklist for "is this server actually ready to run this app" — meant to be run on a brand-new
 * system right after copying the project files, before importing a database backup, so a missing PHP
 * extension or an unwritable folder is caught with a clear fix instead of a confusing 500 error later.
 */
class SystemCheckManager extends Component
{
    /** Extensions this app actually needs at runtime, with why — composer.json doesn't declare ext-*
     *  requirements explicitly, so this list is the real source of truth. Cross-check against `php -m`
     *  if this ever needs updating. */
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql' => 'Database connection (MySQL/MariaDB driver).',
        'mbstring' => 'Required by Laravel core for multi-byte string handling.',
        'openssl' => 'Encryption — session/cookie signing, APP_KEY.',
        'tokenizer' => 'Required by Laravel core.',
        'xml' => 'Required by Laravel core and the sitemap package.',
        'ctype' => 'Required by Laravel core.',
        'json' => 'Required by Laravel core.',
        'curl' => 'Outbound HTTP — fetching remote images (addMediaFromUrl), external API calls.',
        'fileinfo' => 'File upload MIME-type detection (Spatie Media Library).',
        'gd' => 'Image resizing/conversions for uploaded photos (Spatie Media Library / Intervention Image).',
        'exif' => 'Image metadata reading — required by Spatie Media Library, disabled by default on some PHP builds.',
        'zip' => 'Composer package installation, and this Backup page\'s media-files download.',
        'bcmath' => 'Arbitrary-precision math, used by some Composer dependencies.',
        'dom' => 'Required by Laravel core (DomCrawler-adjacent packages, sitemap XML).',
        'filter' => 'Required by Laravel core (validation).',
        'session' => 'Required by Laravel core.',
    ];

    private const REQUIRED_PHP_VERSION = '8.2.0';

    public function render()
    {
        return view('livewire.admin.settings.system-check-manager', [
            'checks' => $this->runChecks(),
        ])->extends('admin.layouts.app')->section('content');
    }

    /** @return array<int, array{label: string, status: string, detail: string, guidance: ?string}> */
    private function runChecks(): array
    {
        $checks = [];

        $checks[] = $this->phpVersionCheck();
        $checks = array_merge($checks, $this->extensionChecks());
        $checks[] = $this->memoryLimitCheck();
        $checks[] = $this->composerCheck();
        $checks[] = $this->envFileCheck();
        $checks[] = $this->appKeyCheck();
        $checks[] = $this->databaseCheck();
        $checks[] = $this->writableCheck('storage/ directory', storage_path());
        $checks[] = $this->writableCheck('bootstrap/cache/ directory', base_path('bootstrap/cache'));
        $checks[] = $this->storageLinkCheck();
        $checks[] = $this->viteBuildCheck();
        $checks[] = $this->pendingMigrationsCheck();
        $checks[] = $this->reverbConfigCheck();

        return $checks;
    }

    private function phpVersionCheck(): array
    {
        $current = PHP_VERSION;
        $ok = version_compare($current, self::REQUIRED_PHP_VERSION, '>=');

        return [
            'label' => 'PHP version',
            'status' => $ok ? 'pass' : 'fail',
            'detail' => "Running {$current}, requires >= ".self::REQUIRED_PHP_VERSION,
            'guidance' => $ok ? null : 'Upgrade PHP on this server to at least '.self::REQUIRED_PHP_VERSION.' — check with your host or, on XAMPP, install a newer PHP bundle.',
        ];
    }

    /** @return array<int, array{label: string, status: string, detail: string, guidance: ?string}> */
    private function extensionChecks(): array
    {
        $rows = [];

        foreach (self::REQUIRED_EXTENSIONS as $extension => $why) {
            $loaded = extension_loaded($extension);
            $rows[] = [
                'label' => "PHP extension: {$extension}",
                'status' => $loaded ? 'pass' : 'fail',
                'detail' => $why,
                'guidance' => $loaded ? null : "Enable it in php.ini (uncomment or add `extension={$extension}`) and restart the web server/PHP-FPM. {$why}",
            ];
        }

        return $rows;
    }

    /** PHP's default 128M is tight for real work once the framework's own request overhead (Livewire,
     *  Blade, session) is already using part of it — hit for real on the Backup page's media zip/database
     *  dump (both now raise their own limit for that one request via ini_set(), but a low baseline here
     *  is still worth flagging since plenty of other operations — large image uploads/conversions,
     *  addMediaFromUrl on a big remote image — have hit the exact same "Allowed memory size exhausted"
     *  fatal in this app before, see docs/modules/module-14-projects.md and module-03's 2026-08-16
     *  entries). */
    private function memoryLimitCheck(): array
    {
        $raw = ini_get('memory_limit');
        $bytes = $this->parseIniBytes($raw);
        $recommendedBytes = 256 * 1024 * 1024;
        $unlimited = $bytes < 0;
        $ok = $unlimited || $bytes >= $recommendedBytes;

        return [
            'label' => 'PHP memory_limit',
            'status' => $ok ? 'pass' : 'warning',
            'detail' => $unlimited ? 'Unlimited.' : "Currently {$raw}.",
            'guidance' => $ok ? null : "At least 256M is recommended (512M+ is safer) — large image uploads/conversions and the Backup page's database/media downloads can exceed 128M. Raise it in php.ini (`memory_limit = 512M`) if you can; the Backup page already raises its own limit for that one request regardless, so this mainly affects other memory-heavy operations.",
        ];
    }

    private function parseIniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function composerCheck(): array
    {
        $exists = file_exists(base_path('vendor/autoload.php'));

        return [
            'label' => 'Composer dependencies',
            'status' => $exists ? 'pass' : 'fail',
            'detail' => $exists ? 'vendor/autoload.php found.' : 'vendor/ directory is missing.',
            'guidance' => $exists ? null : 'Run `composer install` in the project root.',
        ];
    }

    private function envFileCheck(): array
    {
        $exists = file_exists(base_path('.env'));

        return [
            'label' => '.env file',
            'status' => $exists ? 'pass' : 'fail',
            'detail' => $exists ? '.env found.' : '.env is missing.',
            'guidance' => $exists ? null : 'Copy .env.example to .env and fill in this server\'s database credentials, APP_URL, and mail settings.',
        ];
    }

    private function appKeyCheck(): array
    {
        $key = config('app.key');
        $ok = ! empty($key);

        return [
            'label' => 'Application key',
            'status' => $ok ? 'pass' : 'fail',
            'detail' => $ok ? 'APP_KEY is set.' : 'APP_KEY is empty.',
            'guidance' => $ok ? null : 'Run `php artisan key:generate`.',
        ];
    }

    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();
            $name = DB::connection()->getDatabaseName();

            return [
                'label' => 'Database connection',
                'status' => 'pass',
                'detail' => "Connected to \"{$name}\".",
                'guidance' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'label' => 'Database connection',
                'status' => 'fail',
                'detail' => 'Could not connect: '.$e->getMessage(),
                'guidance' => 'Check DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD in .env, and confirm the database exists and MySQL is running.',
            ];
        }
    }

    private function writableCheck(string $label, string $path): array
    {
        $ok = is_dir($path) && is_writable($path);

        return [
            'label' => $label,
            'status' => $ok ? 'pass' : 'fail',
            'detail' => $ok ? 'Writable.' : (is_dir($path) ? 'Exists but is not writable.' : 'Does not exist.'),
            'guidance' => $ok ? null : "Make sure {$path} exists and is writable by the web server user (e.g. `chmod -R 775 " . basename($path) . "` on Linux, or check folder permissions on Windows).",
        ];
    }

    private function storageLinkCheck(): array
    {
        $linkPath = public_path('storage');
        $ok = file_exists($linkPath);

        return [
            'label' => 'Public storage symlink',
            'status' => $ok ? 'pass' : 'warning',
            'detail' => $ok ? 'public/storage exists.' : 'public/storage is missing — uploaded images/files will 404.',
            'guidance' => $ok ? null : 'Run `php artisan storage:link`.',
        ];
    }

    private function viteBuildCheck(): array
    {
        $manifest = public_path('build/manifest.json');
        $ok = file_exists($manifest);

        return [
            'label' => 'Frontend assets built',
            'status' => $ok ? 'pass' : 'fail',
            'detail' => $ok ? 'public/build/manifest.json found.' : 'No compiled assets found — the site will render without CSS/JS.',
            'guidance' => $ok ? null : 'Run `npm install` then `npm run build`.',
        ];
    }

    private function pendingMigrationsCheck(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return [
                    'label' => 'Database migrations',
                    'status' => 'warning',
                    'detail' => 'No migrations table found yet.',
                    'guidance' => 'Run `php artisan migrate` (or import the database backup, which already includes this).',
                ];
            }

            $ran = DB::table('migrations')->count();
            $files = count(glob(database_path('migrations/*.php')) ?: []);
            $ok = $ran >= $files;

            return [
                'label' => 'Database migrations',
                'status' => $ok ? 'pass' : 'warning',
                'detail' => "{$ran} of {$files} migration files have been run.",
                'guidance' => $ok ? null : 'Run `php artisan migrate --force` to apply the remaining migrations.',
            ];
        } catch (\Throwable $e) {
            return [
                'label' => 'Database migrations',
                'status' => 'warning',
                'detail' => 'Could not check — database connection issue (see above).',
                'guidance' => null,
            ];
        }
    }

    private function reverbConfigCheck(): array
    {
        $configured = ! empty(config('broadcasting.connections.reverb.key'))
            && ! empty(config('broadcasting.connections.reverb.secret'));

        return [
            'label' => 'Live chat (Reverb) configuration',
            'status' => $configured ? 'pass' : 'warning',
            'detail' => $configured
                ? 'Reverb credentials are set in .env.'
                : 'REVERB_APP_KEY/REVERB_APP_SECRET not set — real-time chat push will silently fall back to polling.',
            'guidance' => $configured
                ? 'Remember Reverb also needs to be actually running: `php artisan reverb:start` (it does not start on its own).'
                : 'Set REVERB_APP_ID/REVERB_APP_KEY/REVERB_APP_SECRET in .env (any values work for a self-hosted Reverb server), then run `php artisan reverb:start`.',
        ];
    }
}
