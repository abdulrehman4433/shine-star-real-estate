<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Review extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'customer_name',
        'customer_role',
        'rating',
        'content',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 120, 120)
            ->nonQueued();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('photo') ?: null;
    }

    public function getPhotoThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('photo', 'thumb') ?: $this->photo_url;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function cachedActive()
    {
        return Cache::remember('reviews.active', now()->addHour(), function () {
            return static::active()->orderBy('order')->get();
        });
    }
}
