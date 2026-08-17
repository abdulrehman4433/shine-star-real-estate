<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'location',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Cached, active-only, nested item tree for the first menu at the given location
     * (header/footer). Used by the frontend header/footer partials via a View Composer.
     */
    public static function cachedTree(string $location): array
    {
        return Cache::remember("menu.{$location}", now()->addHour(), function () use ($location) {
            $menu = static::location($location)->orderBy('id')->first();

            if (! $menu) {
                return [];
            }

            $items = MenuItem::where('menu_id', $menu->id)->active()->orderBy('order')->get();

            return static::buildTree($items);
        });
    }

    private static function buildTree($items, ?int $parentId = null): array
    {
        return $items
            ->where('parent_id', $parentId)
            ->map(fn (MenuItem $item) => [
                'item' => $item,
                'children' => static::buildTree($items, $item->id),
            ])
            ->values()
            ->all();
    }
}
