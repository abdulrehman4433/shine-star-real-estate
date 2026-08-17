<?php

namespace App\Models\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

trait HasSeo
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'model');
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->seo?->meta_title ?: ($this->title ?? config('app.name'));
    }

    public function getSeoDescriptionAttribute(): string
    {
        if ($this->seo?->meta_description) {
            return $this->seo->meta_description;
        }

        $fallback = $this->excerpt ?? $this->description ?? $this->content ?? '';

        return Str::limit(trim(strip_tags($fallback)), 160, '');
    }

    public function getSeoKeywordsAttribute(): ?string
    {
        return $this->seo?->meta_keywords;
    }

    public function getSeoImageAttribute(): ?string
    {
        return $this->seo?->og_image ?: ($this->featured_image_url ?? null);
    }

    public function getSeoCanonicalAttribute(): string
    {
        return $this->seo?->canonical_url ?: url()->current();
    }

    public function getSeoSchemaAttribute(): array
    {
        if ($this->seo?->schema_json) {
            return $this->seo->schema_json;
        }

        return $this->defaultSeoSchema();
    }

    /** Overridden per-model (Property, BlogPost); generic WebPage fallback otherwise. */
    public function defaultSeoSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $this->seo_title,
            'description' => $this->seo_description,
            'url' => $this->seo_canonical,
        ];
    }

    /** Update (or create) this model's SEO record from an array of the same shape as $fillable on SeoMeta. */
    public function updateSeo(array $attributes): void
    {
        $this->seo()->updateOrCreate([], $attributes);
    }
}
