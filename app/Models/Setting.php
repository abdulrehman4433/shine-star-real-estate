<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['key', 'value', 'type'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value, string $type = 'string'): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type],
        );

        Cache::forget("settings.{$key}");

        return $setting;
    }

    /** URL of an uploaded file setting (e.g. site_logo, site_favicon), or null if none uploaded.
     *
     * Returns a relative path (e.g. /storage/1/logo.png) instead of an absolute URL
     * so images work regardless of the APP_URL or subdirectory deployment.
     */
    public static function getFileUrl(string $key): ?string
    {
        return Cache::rememberForever("settings.{$key}.file", function () use ($key) {
            $setting = static::query()->where('key', $key)->first();

            $url = $setting?->getFirstMediaUrl('file');

            if ($url) {
                // Strip scheme + host to get a relative path that works with any domain
                $url = parse_url($url, PHP_URL_PATH);
            }

            return $url ?: null;
        });
    }

    public static function setFile(string $key, $uploadedFile): self
    {
        $setting = static::query()->firstOrCreate(['key' => $key], ['value' => '', 'type' => 'file']);

        $setting->addMedia($uploadedFile->getRealPath())
            ->usingFileName($uploadedFile->getClientOriginalName())
            ->toMediaCollection('file');

        Cache::forget("settings.{$key}.file");

        return $setting;
    }

    /** For credential-like values (currently just the SMTP password) — encrypted at rest using the
     *  app's own APP_KEY (Laravel's Crypt facade), same as everything else this app already trusts
     *  APP_KEY to protect (session/cookie signing). Not cached, unlike get() — a plain
     *  Cache::rememberForever() here would mean the decrypted secret sits in the cache store
     *  (file/redis/etc.) in plaintext indefinitely, which defeats the point of encrypting it in the
     *  database in the first place. This value is only read once per request at most (see
     *  AppServiceProvider's mail config override), so the extra query is not a real cost. */
    public static function getEncrypted(string $key, ?string $default = null): ?string
    {
        $raw = static::query()->where('key', $key)->value('value');

        if ($raw === null || $raw === '') {
            return $default;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // A value saved before encryption was added, or APP_KEY rotated — fail closed rather than
            // returning garbage that would be sent to a real SMTP server as a password.
            return $default;
        }
    }

    public static function setEncrypted(string $key, ?string $value): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value ? Crypt::encryptString($value) : '', 'type' => 'encrypted'],
        );

        Cache::forget("settings.{$key}");

        return $setting;
    }

    public const CURRENCIES = [
        'PKR' => 'PKR ',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'AED' => 'AED ',
        'SAR' => 'SAR ',
    ];

    public static function currencySymbol(): string
    {
        return static::CURRENCIES[static::get('currency', 'PKR')] ?? static::get('currency', 'PKR').' ';
    }
}
