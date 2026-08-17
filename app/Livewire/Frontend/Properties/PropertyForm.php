<?php

namespace App\Livewire\Frontend\Properties;

use App\Enums\PropertyPriceType;
use App\Enums\PropertyStatus;
use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\Property;
use App\Models\PropertyAmenity;
use App\Models\PropertyCategory;
use App\Models\PropertyType;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class PropertyForm extends Component
{
    use Notifies, WithFileUploads;

    #[Locked]
    public ?int $propertyId = null;

    public string $title = '';

    public string $description = '';

    public string $category_id = '';

    public string $type_id = '';

    public string $price = '';

    public string $price_type = 'fixed';

    public string $address = '';

    public string $city = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public string $size = '';

    public string $bedrooms = '';

    public string $bathrooms = '';

    public array $selectedAmenities = [];

    public array $customFeatures = [];

    public string $newCategoryName = '';

    public string $newTypeName = '';

    public string $newAmenityName = '';

    public $featuredImage = null;

    public array $galleryImages = [];

    public string $seoMetaTitle = '';

    public string $seoMetaDescription = '';

    public string $seoMetaKeywords = '';

    public string $seoCanonicalUrl = '';

    public function mount(?Property $property = null): void
    {
        if ($property && $property->exists) {
            $this->authorize('update', $property);

            $this->propertyId = $property->id;
            $this->title = $property->title;
            $this->description = (string) $property->description;
            $this->category_id = (string) $property->category_id;
            $this->type_id = (string) $property->type_id;
            $this->price = (string) $property->price;
            $this->price_type = $property->price_type;
            $this->address = (string) $property->address;
            $this->city = (string) $property->city;
            $this->lat = $property->lat ? (float) $property->lat : null;
            $this->lng = $property->lng ? (float) $property->lng : null;
            $this->size = (string) $property->size;
            $this->bedrooms = (string) $property->bedrooms;
            $this->bathrooms = (string) $property->bathrooms;
            $this->selectedAmenities = $property->amenities->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->customFeatures = $property->features->map(fn ($f) => ['name' => $f->name, 'value' => $f->value])->all();
            $this->seoMetaTitle = (string) $property->seo?->meta_title;
            $this->seoMetaDescription = (string) $property->seo?->meta_description;
            $this->seoMetaKeywords = (string) $property->seo?->meta_keywords;
            $this->seoCanonicalUrl = (string) $property->seo?->canonical_url;
        } else {
            $this->authorize('create', Property::class);
        }

        if (empty($this->customFeatures)) {
            $this->customFeatures = [['name' => '', 'value' => '']];
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:property_categories,id',
            'type_id' => 'required|exists:property_types,id',
            'price' => 'required|numeric|min:0',
            'price_type' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, PropertyPriceType::cases())),
            'address' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'size' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'featuredImage' => 'nullable|image|max:4096',
            'galleryImages.*' => 'nullable|image|max:4096',
            'selectedAmenities' => 'array',
            'selectedAmenities.*' => 'exists:property_amenities,id',
            'customFeatures.*.name' => 'nullable|string|max:255',
            'customFeatures.*.value' => 'nullable|string|max:255',
            'seoMetaTitle' => 'nullable|string|max:255',
            'seoMetaDescription' => 'nullable|string|max:255',
            'seoMetaKeywords' => 'nullable|string|max:255',
            'seoCanonicalUrl' => 'nullable|string|max:255',
        ];
    }

    public function isAdminContext(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'super-admin']) ?? false;
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function quickAddCategory(): void
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);

        $category = PropertyCategory::create([
            'name' => $this->newCategoryName,
            'is_active' => true,
            'order' => (PropertyCategory::whereNull('parent_id')->max('order') ?? -1) + 1,
        ]);

        $this->category_id = (string) $category->id;
        $this->newCategoryName = '';
    }

    public function quickAddType(): void
    {
        $this->validate(['newTypeName' => 'required|string|max:255']);

        $type = PropertyType::create([
            'name' => $this->newTypeName,
            'is_active' => true,
            'order' => (PropertyType::max('order') ?? -1) + 1,
        ]);

        $this->type_id = (string) $type->id;
        $this->newTypeName = '';
    }

    public function quickAddAmenity(): void
    {
        $this->validate([
            'newAmenityName' => ['required', 'string', 'max:255', Rule::unique('property_amenities', 'name')],
        ]);

        $amenity = PropertyAmenity::create([
            'name' => $this->newAmenityName,
            'is_active' => true,
            'order' => (PropertyAmenity::max('order') ?? -1) + 1,
        ]);

        $this->selectedAmenities[] = (string) $amenity->id;
        $this->newAmenityName = '';
    }

    public function addFeatureRow(): void
    {
        $this->customFeatures[] = ['name' => '', 'value' => ''];
    }

    public function removeFeatureRow(int $index): void
    {
        unset($this->customFeatures[$index]);
        $this->customFeatures = array_values($this->customFeatures);
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $property = Property::findOrFail($this->propertyId);
        $this->authorize('update', $property);

        $property->media()->where('id', $mediaId)->where('collection_name', 'gallery')->first()?->delete();
    }

    public function save()
    {
        $this->validate();

        $isAdmin = $this->isAdminContext();

        $property = $this->propertyId
            ? Property::findOrFail($this->propertyId)
            : new Property(['user_id' => auth()->id()]);

        $this->authorize($property->exists ? 'update' : 'create', $property->exists ? $property : Property::class);

        // Admin-created listings are auto-approved; admin edits keep the existing status.
        // Agent/agency edits resubmit for moderation, matching the "resubmit for approval" notice below.
        $status = $property->exists
            ? ($isAdmin ? $property->status : PropertyStatus::Pending->value)
            : ($isAdmin ? PropertyStatus::Approved->value : PropertyStatus::Pending->value);

        $property->fill([
            'category_id' => $this->category_id,
            'type_id' => $this->type_id,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'address' => $this->address ?: null,
            'city' => $this->city,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'size' => $this->size ?: null,
            'bedrooms' => $this->bedrooms !== '' ? $this->bedrooms : null,
            'bathrooms' => $this->bathrooms !== '' ? $this->bathrooms : null,
            'status' => $status,
            'rejection_reason' => null,
        ]);

        $property->save();

        $property->amenities()->sync($this->selectedAmenities);

        $property->features()->delete();
        foreach ($this->customFeatures as $feature) {
            if (trim($feature['name'] ?? '') !== '') {
                $property->features()->create([
                    'name' => $feature['name'],
                    'value' => $feature['value'] ?? '',
                ]);
            }
        }

        if ($this->featuredImage) {
            $property->addMedia($this->featuredImage->getRealPath())
                ->usingFileName($this->featuredImage->getClientOriginalName())
                ->toMediaCollection('featured');
        }

        foreach ($this->galleryImages as $image) {
            $property->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery');
        }

        $property->updateSeo([
            'meta_title' => $this->seoMetaTitle ?: null,
            'meta_description' => $this->seoMetaDescription ?: null,
            'meta_keywords' => $this->seoMetaKeywords ?: null,
            'canonical_url' => $this->seoCanonicalUrl ?: null,
        ]);

        GenerateSitemap::dispatch();

        $this->flashSuccess('Property saved.');

        $redirectRoute = match (true) {
            // Editing/creating from the "My Properties" hub always returns there,
            // regardless of the user's role.
            request()->routeIs('my.properties.*') => 'my.properties.index',
            $isAdmin => 'admin.properties.index',
            auth()->user()?->hasAnyRole(['agent', 'agency']) => 'agent.listings.index',
            default => 'my.properties.index',
        };

        return redirect()->route($redirectRoute);
    }

    public function render()
    {
        $isAdmin = $this->isAdminContext();

        return view('livewire.frontend.properties.property-form', [
            'categories' => PropertyCategory::query()->active()->roots()->with(['children' => fn ($q) => $q->active()])->orderBy('order')->get(),
            'types' => PropertyType::query()->active()->orderBy('order')->get(),
            'amenities' => PropertyAmenity::query()->active()->orderBy('order')->get(),
            'existingGallery' => $this->propertyId ? Property::find($this->propertyId)?->getMedia('gallery') : collect(),
            'isAdminContext' => $isAdmin,
        ])->extends($isAdmin ? 'admin.layouts.app' : 'frontend.layouts.app')->section('content');
    }
}
