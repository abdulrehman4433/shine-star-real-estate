<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CdnAsset extends Model
{
    protected $fillable = [
        'location',
        'type',
        'url',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Get active CDN assets for a given location (header/footer), cached.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function cachedForLocation(string $location)
    {
        return Cache::remember("cdn_assets.{$location}", now()->addHour(), function () use ($location) {
            return static::active()
                ->inLocation($location)
                ->orderBy('order')
                ->get();
        });
    }
}
