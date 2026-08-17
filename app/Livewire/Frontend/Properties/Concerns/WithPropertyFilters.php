<?php

namespace App\Livewire\Frontend\Properties\Concerns;

use App\Models\PropertyCategory;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;

/**
 * Shared filter state + query helpers for the public property listing and the
 * authenticated "My Properties" hub, so the two pages can't drift apart.
 *
 * Components using this trait must also use Livewire's WithPagination.
 */
trait WithPropertyFilters
{
    #[Url]
    public string $keyword = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $type = '';

    #[Url]
    public ?float $min_price = null;

    #[Url]
    public ?float $max_price = null;

    #[Url]
    public ?int $bedrooms = null;

    #[Url]
    public string $city = '';

    #[Url]
    public string $layout = 'grid';

    public function updating($property): void
    {
        if ($property !== 'layout') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['keyword', 'category', 'type', 'min_price', 'max_price', 'bedrooms', 'city']);
        $this->resetPage();
    }

    /** Apply the active filters to a property query builder. */
    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->keyword, fn ($q) => $q->where(function ($qq) {
                $qq->where('title', 'like', "%{$this->keyword}%")
                    ->orWhere('city', 'like', "%{$this->keyword}%")
                    ->orWhere('address', 'like', "%{$this->keyword}%");
            }))
            ->when($this->category, fn ($q) => $q->whereIn('category_id', $this->categoryIdsForFilter()))
            ->when($this->type, fn ($q) => $q->where('type_id', $this->type))
            ->when($this->min_price, fn ($q) => $q->where('price', '>=', $this->min_price))
            ->when($this->max_price, fn ($q) => $q->where('price', '<=', $this->max_price))
            ->when($this->bedrooms, fn ($q) => $q->where('bedrooms', '>=', $this->bedrooms))
            ->when($this->city, fn ($q) => $q->where('city', 'like', "%{$this->city}%"));
    }

    /**
     * Selecting a parent category (e.g. "Commercial") should also match properties filed under any
     * of its sub-categories (e.g. "Office"/"Shop"/"Warehouse") — a bare `where('category_id', ...)`
     * only ever matched the exact row selected, so picking a parent silently hid everything under it.
     * Selecting a child (leaf) category still behaves exactly as before: descendantIds() returns an
     * empty array for a category with no children, so the id list is just the one selected.
     */
    protected function categoryIdsForFilter(): array
    {
        $category = PropertyCategory::find($this->category);

        if (! $category) {
            return [$this->category];
        }

        return array_merge([$category->id], $category->descendantIds());
    }

    protected function categories(): \Illuminate\Support\Collection
    {
        return Cache::remember('property_categories.tree', now()->addHour(), function () {
            return PropertyCategory::query()->active()->roots()->with(['children' => fn ($q) => $q->active()])->orderBy('order')->get();
        });
    }

    protected function types(): \Illuminate\Support\Collection
    {
        return Cache::remember('property_types.active', now()->addHour(), function () {
            return PropertyType::query()->active()->orderBy('order')->get();
        });
    }
}
