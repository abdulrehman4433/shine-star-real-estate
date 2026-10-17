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
        'video_urls',
        'is_featured',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'video_urls' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Blocks are DB-cascaded when a project is deleted (project_blocks.project_id has
        // cascadeOnDelete), and a DB-level cascade fires no model events — which would orphan
        // each block's media rows/files now that blocks own media. Delete them as models first
        // so Spatie's per-model cleanup runs for every block before the project row goes.
        static::deleting(function (Project $project) {
            $project->blocks()->get()->each->delete();
        });
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
        $this->addMediaCollection('videos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Scoped to the image collections only: without performOnCollections() Spatie would also
        // try to build a thumb from uploaded video files (via its FFMpeg-backed Video generator),
        // which fatals on any host without the ffmpeg binaries installed — right after the original
        // file was already saved, i.e. the same silent "half-succeed" failure mode the
        // addMediaFromUrl memory-limit gotcha causes (see docs/modules/module-14-projects.md).
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320)
            ->nonQueued()
            ->performOnCollections('cover', 'gallery');
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

    /** Every video link for this project. Prefers the multi-value video_urls column (the source of
     *  truth since the multi-video editor was added); falls back to the legacy single video_url
     *  column for rows saved before it existed. Returns a plain, non-empty-string-only list. */
    public function videoLinks(): array
    {
        $links = array_values(array_filter($this->video_urls ?? [], fn ($url) => is_string($url) && trim($url) !== ''));

        if ($links !== []) {
            return $links;
        }

        return $this->video_url ? [$this->video_url] : [];
    }

    /** Embeddable iframe src for every video link (YouTube/Vimeo share links converted). */
    public function getVideoEmbedUrlsAttribute(): array
    {
        return array_map(fn ($url) => self::embedUrlFor($url), $this->videoLinks());
    }

    /** First video's embed src, kept for back-compat with the original single-video rendering. */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        return $this->getVideoEmbedUrlsAttribute()[0] ?? null;
    }

    /** Convert a plain YouTube/Vimeo watch URL into its embeddable iframe-src form, so a stored
     *  video link can be pasted in the normal "share" format instead of requiring an already-built
     *  embed URL. Non-YouTube/Vimeo URLs (e.g. a direct file link) are returned unchanged.
     *  Public static so ProjectBlock (which stores its own video links) can reuse it. */
    public static function embedUrlFor(string $url): string
    {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $url;
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProjectBlock::class)->orderBy('order');
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
