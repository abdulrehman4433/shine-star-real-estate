<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Properties', 'route' => 'admin.properties.index'],
        ['label' => $property->title],
    ]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="ssm-page-header__title mb-2">{{ $property->title }}</div>
            <span class="badge {{ $property->statusEnum()->badgeClass() }}">{{ ucfirst($property->status) }}</span>
            @if ($property->is_featured)
                <span class="badge text-bg-warning">Featured</span>
            @endif
            @if ($property->status === 'rejected' && $property->rejection_reason)
                <div class="text-muted small mt-2">Rejection reason: {{ $property->rejection_reason }}</div>
            @endif
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Basic Information</h2>
            <div class="row">
                <div class="col-md-4 mb-2"><span class="text-muted small">Category</span><div>{{ $property->category->name ?? '—' }}</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Type</span><div>{{ $property->type->name ?? '—' }}</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Owner</span><div>{{ $property->owner->name ?? '—' }} ({{ $property->owner->email ?? '—' }})</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Price</span><div>{{ $property->formatted_price }} @if ($property->price_type === 'negotiable')<span class="text-muted">(Negotiable)</span>@endif</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Size</span><div>{{ $property->size ? number_format((float) $property->size).' sqft' : '—' }}</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Bedrooms / Bathrooms</span><div>{{ $property->bedrooms ?? '—' }} / {{ $property->bathrooms ?? '—' }}</div></div>
            </div>
            @if ($property->description)
                <hr>
                <span class="text-muted small">Description</span>
                <p class="mb-0">{{ $property->description }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Location</h2>
            <div class="row mb-2">
                <div class="col-md-8"><span class="text-muted small">Address</span><div>{{ $property->address ?: '—' }}</div></div>
                <div class="col-md-4"><span class="text-muted small">City</span><div>{{ $property->city ?: '—' }}</div></div>
            </div>

            @if ($property->lat && $property->lng)
                <div wire:ignore x-data="{
                    init() {
                        const map = L.map($refs.map).setView([{{ $property->lat }}, {{ $property->lng }}], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19,
                        }).addTo(map);
                        L.marker([{{ $property->lat }}, {{ $property->lng }}]).addTo(map);
                    }
                }">
                    <div x-ref="map" style="height: 300px;" class="rounded"></div>
                </div>
            @else
                <p class="text-muted small mb-0">No coordinates set.</p>
            @endif
        </div>
    </div>

    @if ($property->amenities->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Amenities</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($property->amenities as $amenity)
                        <span class="badge text-bg-light border">{{ $amenity->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($property->features->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Additional Features</h2>
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach ($property->features as $feature)
                            <tr>
                                <th style="width: 40%;">{{ $feature->name }}</th>
                                <td>{{ $feature->value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Media</h2>
            <div class="d-flex flex-wrap gap-2">
                @if ($property->featured_image_url)
                    <img src="{{ $property->featured_thumb_url }}" class="rounded" width="160" height="107" style="object-fit: cover;" title="Featured image">
                @endif
                @foreach ($gallery as $media)
                    <img src="{{ $media->getUrl() }}" class="rounded" width="120" height="80" style="object-fit: cover;">
                @endforeach
                @if (! $property->featured_image_url && $gallery->isEmpty())
                    <p class="text-muted small mb-0">No media uploaded.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">SEO</h2>
            <div class="row">
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Title</span><div>{{ $property->seo?->meta_title ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Description</span><div>{{ $property->seo?->meta_description ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Keywords</span><div>{{ $property->seo?->meta_keywords ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Canonical URL</span><div>{{ $property->seo?->canonical_url ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><span class="text-muted small">Created</span><div>{{ $property->created_at->format('M j, Y H:i') }}</div></div>
                <div class="col-md-4"><span class="text-muted small">Last Updated</span><div>{{ $property->updated_at->format('M j, Y H:i') }}</div></div>
                <div class="col-md-4"><span class="text-muted small">Expiry Date</span><div>{{ $property->expiry_date?->format('M j, Y') ?? '—' }}</div></div>
            </div>
        </div>
    </div>
</div>
