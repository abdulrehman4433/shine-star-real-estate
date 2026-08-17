<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FooterWidget extends Model
{
    protected $fillable = [
        'title',
        'type',
        'content',
        'column',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Cached, active-only widgets grouped by column. Used by the frontend footer via a View Composer. */
    public static function cachedColumns()
    {
        return Cache::remember('footer_widgets', now()->addHour(), function () {
            return static::active()->orderBy('order')->get()->groupBy('column');
        });
    }
}
