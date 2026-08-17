<div class="container py-5" style="max-width: 900px;">
    @if ($isAdminContext)
        @include('admin.partials.breadcrumb', ['items' => [
            ['label' => 'Properties', 'route' => 'admin.properties.index'],
            ['label' => $propertyId ? 'Edit Property' : 'Add Property'],
        ]])
    @endif

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">{{ $propertyId ? 'Edit Property' : ($isAdminContext ? 'Add Property' : 'New Listing') }}</div>
    </div>

    @if ($propertyId && ! $isAdminContext)
        <div class="alert alert-info">Saving changes will resubmit this listing for admin approval.</div>
    @elseif (! $propertyId && $isAdminContext)
        <div class="alert alert-info">Listings added here are approved and published immediately.</div>
    @endif

    <form wire:submit="save">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-info-circle"></i> Basic Information</h2>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea wire:model="description" rows="5" class="form-control @error('description') is-invalid @enderror"></textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select wire:model="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">Select category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @foreach ($cat->children as $child)
                                    <option value="{{ $child->id }}">&nbsp;&nbsp;— {{ $child->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="input-group input-group-sm mt-2">
                            <input type="text" wire:model="newCategoryName" placeholder="New category name" class="form-control">
                            <button type="button" class="btn btn-outline-secondary" wire:click="quickAddCategory">+ Add</button>
                        </div>
                        @error('newCategoryName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type</label>
                        <select wire:model="type_id" class="form-select @error('type_id') is-invalid @enderror">
                            <option value="">Select type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="input-group input-group-sm mt-2">
                            <input type="text" wire:model="newTypeName" placeholder="New property type name" class="form-control">
                            <button type="button" class="btn btn-outline-secondary" wire:click="quickAddType">+ Add</button>
                        </div>
                        @error('newTypeName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" wire:model="price" class="form-control @error('price') is-invalid @enderror">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price type</label>
                        <select wire:model="price_type" class="form-select @error('price_type') is-invalid @enderror">
                            <option value="fixed">Fixed</option>
                            <option value="negotiable">Negotiable</option>
                        </select>
                        @error('price_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-geo-alt"></i> Location</h2>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" wire:model="address" class="form-control @error('address') is-invalid @enderror">
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" wire:model="city" class="form-control @error('city') is-invalid @enderror">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <p class="form-text mb-2">Click on the map to drop a pin at the property's location.</p>

                <div
                    wire:ignore
                    x-data="{
                        map: null,
                        marker: null,
                        init() {
                            const startLat = {{ $lat ?? 20 }};
                            const startLng = {{ $lng ?? 0 }};
                            const startZoom = {{ $lat && $lng ? 13 : 2 }};

                            this.map = L.map($refs.map).setView([startLat, startLng], startZoom);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap contributors',
                                maxZoom: 19,
                            }).addTo(this.map);

                            @if ($lat && $lng)
                                this.marker = L.marker([{{ $lat }}, {{ $lng }}]).addTo(this.map);
                            @endif

                            this.map.on('click', (e) => {
                                if (this.marker) {
                                    this.marker.setLatLng(e.latlng);
                                } else {
                                    this.marker = L.marker(e.latlng).addTo(this.map);
                                }

                                $wire.setLocation(e.latlng.lat, e.latlng.lng);
                            });
                        }
                    }"
                >
                    <div x-ref="map" style="height: 320px;" class="rounded mb-2"></div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <span class="form-text">Latitude: {{ $lat ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="form-text">Longitude: {{ $lng ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-rulers"></i> Specifications</h2>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Size (sqft)</label>
                        <input type="number" step="0.01" wire:model="size" class="form-control @error('size') is-invalid @enderror">
                        @error('size') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bedrooms</label>
                        <input type="number" wire:model="bedrooms" class="form-control @error('bedrooms') is-invalid @enderror">
                        @error('bedrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bathrooms</label>
                        <input type="number" wire:model="bathrooms" class="form-control @error('bathrooms') is-invalid @enderror">
                        @error('bathrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <label class="form-label">Amenities</label>
                @if ($amenities->isNotEmpty())
                    <div class="row row-cols-2 row-cols-md-3">
                        @foreach ($amenities as $amenity)
                            <div class="col">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="amenity-{{ $amenity->id }}"
                                        value="{{ $amenity->id }}" wire:model="selectedAmenities">
                                    <label class="form-check-label" for="amenity-{{ $amenity->id }}">{{ $amenity->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="input-group input-group-sm mt-2" style="max-width: 320px;">
                    <input type="text" wire:model="newAmenityName" placeholder="New amenity name" class="form-control">
                    <button type="button" class="btn btn-outline-secondary" wire:click="quickAddAmenity">+ Add</button>
                </div>
                @error('newAmenityName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                    <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-list-check"></i> Additional Features</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addFeatureRow">
                        <i class="bi bi-plus-lg"></i> Add Feature
                    </button>
                </div>

                @foreach ($customFeatures as $index => $feature)
                    <div class="row g-2 mb-2">
                        <div class="col-5">
                            <input type="text" placeholder="Name (e.g. Furnishing)"
                                wire:model="customFeatures.{{ $index }}.name" class="form-control">
                        </div>
                        <div class="col-5">
                            <input type="text" placeholder="Value (e.g. Fully furnished)"
                                wire:model="customFeatures.{{ $index }}.value" class="form-control">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-outline-danger w-100" wire:click="removeFeatureRow({{ $index }})">
                                &times;
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-images"></i> Photos</h2>

                <div class="mb-3">
                    <label class="form-label">Featured image</label>
                    <input type="file" wire:model="featuredImage" accept="image/*" class="form-control @error('featuredImage') is-invalid @enderror">
                    @error('featuredImage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="featuredImage" class="form-text">Uploading...</div>
                    @if ($featuredImage)
                        <img src="{{ $featuredImage->temporaryUrl() }}" class="mt-2 rounded" width="120" height="80" style="object-fit: cover;">
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Gallery images</label>
                    <input type="file" wire:model="galleryImages" accept="image/*" multiple class="form-control @error('galleryImages.*') is-invalid @enderror">
                    @error('galleryImages.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="galleryImages" class="form-text">Uploading...</div>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($galleryImages as $image)
                            <img src="{{ $image->temporaryUrl() }}" class="rounded" width="100" height="80" style="object-fit: cover;">
                        @endforeach

                        @foreach ($existingGallery as $media)
                            <div class="position-relative">
                                <img src="{{ $media->getUrl() }}" class="rounded" width="100" height="80" style="object-fit: cover;">
                                <button type="button"
                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                    style="width: 20px; height: 20px; line-height: 1;"
                                    wire:click="removeGalleryImage({{ $media->id }})">&times;</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @include('admin.partials.seo-fields')

        <div class="d-flex justify-content-end pt-2">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                {{ $propertyId ? 'Save Changes' : ($isAdminContext ? 'Add Property' : 'Submit Listing') }}
            </button>
        </div>
    </form>
</div>
