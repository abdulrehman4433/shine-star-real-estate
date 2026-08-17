<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Project extends Model implements HasMedia
{
    use HasFactory, HasSeo, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'society_name',
        'developer_name',
        'type',
        'description',
        'address',
        'city',
        'lat',
        'lng',
        'contact_phone',
        'contact_email',
        'video_url',
        'is_featured',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('brochure')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320)
            ->nonQueued();
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover') ?: null;
    }

    public function getCoverThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover', 'thumb') ?: $this->cover_url;
    }

    public function getBrochureUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('brochure') ?: null;
    }

    /** Convert a plain YouTube/Vimeo watch URL into its embeddable iframe-src form, so the stored
     *  video_url can be pasted in the normal "share" format instead of requiring an already-built
     *  embed URL. Returns null if video_url isn't set or doesn't match a known host. */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]+)/', $this->video_url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $this->video_url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $this->video_url;
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProjectBlock::class)->orderBy('order');
    }

    public function plotSizes(): HasMany
    {
        return $this->hasMany(ProjectPlotSize::class)->orderBy('order');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(PropertyAmenity::class, 'project_amenity_project');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function typeEnum(): ProjectType
    {
        return ProjectType::from($this->type);
    }

    public function defaultSeoSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ResidentialComplex',
            'name' => $this->title,
            'description' => $this->seo_description,
            'url' => $this->seo_canonical,
        ];
    }
}
