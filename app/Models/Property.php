<?php

namespace App\Models;

use App\Enums\PropertyStatus;
use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Property extends Model implements HasMedia
{
    use HasFactory, HasSeo, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'category_id',
        'type_id',
        'title',
        'slug',
        'description',
        'price',
        'price_type',
        'status',
        'rejection_reason',
        'address',
        'city',
        'lat',
        'lng',
        'size',
        'bedrooms',
        'bathrooms',
        'is_featured',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'size' => 'decimal:2',
            'is_featured' => 'boolean',
            'expiry_date' => 'date',
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
        $this->addMediaCollection('featured')->singleFile();
        $this->addMediaCollection('gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 320)
            ->nonQueued();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured') ?: null;
    }

    public function getFormattedPriceAttribute(): string
    {
        return Setting::currencySymbol().number_format((float) $this->price, 0);
    }

    public function getFeaturedThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured', 'thumb') ?: $this->featured_image_url;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'type_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(PropertyAmenity::class, 'property_amenity_property');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PropertyFeature::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favoritedBy()->where('user_id', $user->id)->exists();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', PropertyStatus::Approved->value);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function statusEnum(): PropertyStatus
    {
        return PropertyStatus::from($this->status);
    }

    public function isPending(): bool
    {
        return $this->status === PropertyStatus::Pending->value;
    }

    public function isApproved(): bool
    {
        return $this->status === PropertyStatus::Approved->value;
    }

    public function isExpired(): bool
    {
        return $this->status === PropertyStatus::Expired->value
            || ($this->expiry_date && $this->expiry_date->isPast());
    }

    public function defaultSeoSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $this->title,
            'description' => $this->seo_description,
            'url' => $this->seo_canonical,
            'image' => $this->seo_image,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $this->address,
                'addressLocality' => $this->city,
            ],
            'geo' => $this->lat && $this->lng ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $this->lat,
                'longitude' => (float) $this->lng,
            ] : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => (float) $this->price,
                'priceCurrency' => Setting::get('currency', 'PKR'),
                'availability' => $this->isApproved() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ];
    }
}
