<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, HasSeo, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'status',
        'template',
        'page_template_id',
        'meta_title',
        'meta_description',
        'html',
        'css',
        'js',
        'order',
    ];

    public function pageTemplate()
    {
        return $this->belongsTo(\App\Models\PageTemplate::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('order');
    }

    /**
     * Render the stored HTML with page data substitution.
     */
    public function renderHtml(array $pageData = []): string
    {
        if (empty($this->html)) {
            return '';
        }

        $data = array_merge([
            'pageTitle' => $pageData['title'] ?? $this->title,
            'pageSlug' => $pageData['slug'] ?? $this->slug,
            'pageMetaTitle' => $pageData['meta_title'] ?? ($this->meta_title ?? ''),
            'pageMetaDescription' => $pageData['meta_description'] ?? ($this->meta_description ?? ''),
        ], $pageData);

        return PageTemplate::safeSubstitute($this->html, $data);
    }

    /**
     * Render the stored CSS. No settings-driven substitution anymore (the Settings
     * tab/panel was removed) — any {{ $var }} placeholders left over from before just
     * render as empty via safeSubstitute's own "missing key" fallback.
     */
    public function renderCss(): string
    {
        if (empty($this->css)) {
            return '';
        }

        return PageTemplate::safeSubstitute($this->css, []);
    }

    /**
     * Render the JS template.
     */
    public function renderJs(): string
    {
        return $this->js ?? '';
    }

    public function scopePublished($query)
    {
        return $query->where('status', PageStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published->value;
    }

    public function statusEnum(): PageStatus
    {
        return PageStatus::from($this->status);
    }

    /** Falls back to this page's own meta_title/meta_description columns (Module 7) before the generic title. */
    public function getSeoTitleAttribute(): string
    {
        return $this->seo?->meta_title ?: ($this->meta_title ?: $this->title);
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->seo?->meta_description ?: (string) $this->meta_description;
    }
}
