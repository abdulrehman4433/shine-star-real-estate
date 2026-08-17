@if ($properties->isNotEmpty())
    <!-- ============ FEATURED PROPERTY FOR SALE ============ -->
    <section class="section-pad">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-title-tag">Best Property Listings</div>
                <h2 class="section-title">Property For Sale</h2>
                <p class="section-desc mx-auto">A handpicked selection of our featured listings, chosen for their location, value, and quality.</p>
            </div>

            <div class="row g-4">
                @foreach ($properties as $property)
                    <div class="col-md-6 col-lg-4">
                        <div class="property-card">
                            <div class="property-img-wrap">
                                <span class="property-badge">{{ $property->type->name ?? 'Property' }}</span>
                                @if ($property->featured_thumb_url)
                                    <img src="{{ $property->featured_thumb_url }}" alt="{{ $property->title }}">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted small">No Image</div>
                                @endif
                            </div>
                            <div class="property-body">
                                <div class="property-price">
                                    {{ $property->formatted_price }}
                                    @if ($property->price_type === 'negotiable')
                                        <span class="fw-normal text-muted" style="font-size: 12px;">(Negotiable)</span>
                                    @endif
                                </div>
                                <h5 class="property-title">
                                    <a href="{{ route('properties.show', $property) }}">{{ $property->title }}</a>
                                </h5>
                                <div class="property-feats">
                                    @if ($property->bedrooms)
                                        <span><i class="bi bi-house-door" style="margin-right: 5px;"></i>{{ $property->bedrooms }} Beds</span>
                                    @endif
                                    @if ($property->bathrooms)
                                        <span><i class="bi bi-droplet" style="margin-right: 5px;"></i>{{ $property->bathrooms }} Baths</span>
                                    @endif
                                    @if ($property->size)
                                        <span><i class="bi bi-arrows-fullscreen" style="margin-right: 5px;"></i>{{ number_format($property->size) }} sqft</span>
                                    @endif
                                </div>
                                <div class="property-loc">
                                    <i class="bi bi-geo-alt"></i>
                                    {{ $property->address ?: $property->city }}
                                </div>
                                <a href="{{ route('properties.show', $property) }}" class="property-view-btn">View</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-5">
                <a href="{{ route('properties.index') }}" class="btn-primary-custom">Browse More Properties</a>
            </div>
        </div>
    </section>
@endif
