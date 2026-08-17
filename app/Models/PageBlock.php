<?php

namespace App\Models;

use App\Enums\PageBlockType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PageBlock extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'page_id',
        'type',
        'content',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_image')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('hero_image') ?: null;
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function typeEnum(): PageBlockType
    {
        return PageBlockType::from($this->type);
    }
}
