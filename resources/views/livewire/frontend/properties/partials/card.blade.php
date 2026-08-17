@php $horizontal = $horizontal ?? false; @endphp

<div class="card h-100 {{ $horizontal ? 'flex-row' : '' }}">
    <a href="{{ route('properties.show', $property) }}" class="{{ $horizontal ? 'flex-shrink-0' : '' }}">
        @if ($property->featured_thumb_url)
            <img src="{{ $property->featured_thumb_url }}"
                alt="{{ $property->title }}"
                class="{{ $horizontal ? 'h-100' : 'card-img-top' }}"
                style="{{ $horizontal ? 'width: 240px; object-fit: cover;' : 'height: 200px; object-fit: cover;' }}">
        @else
            <div class="bg-light d-flex align-items-center justify-content-center text-muted small {{ $horizontal ? 'h-100' : '' }}"
                style="{{ $horizontal ? 'width: 240px;' : 'height: 200px;' }}">
                No Image
            </div>
        @endif
    </a>

    <div class="card-body">
        @if ($property->is_featured)
            <span class="badge text-bg-warning mb-1">Featured</span>
        @endif

        <div class="d-flex justify-content-between align-items-start">
            <h5 class="card-title mb-1">
                <a href="{{ route('properties.show', $property) }}" class="text-decoration-none text-dark">
                    {{ $property->title }}
                </a>
            </h5>
            @livewire('frontend.properties.favorite-button', ['property' => $property], key('favorite-card-'.$property->id))
        </div>

        <p class="text-muted small mb-2">{{ $property->city }}</p>

        <p class="fw-bold mb-2">
            {{ $property->formatted_price }}
            @if ($property->price_type === 'negotiable')
                <span class="badge text-bg-light text-muted fw-normal">Negotiable</span>
            @endif
        </p>

        <div class="d-flex gap-3 text-muted small">
            @if ($property->bedrooms)
                <span>{{ $property->bedrooms }} bed</span>
            @endif
            @if ($property->bathrooms)
                <span>{{ $property->bathrooms }} bath</span>
            @endif
            @if ($property->size)
                <span>{{ number_format($property->size) }} sqft</span>
            @endif
        </div>

        <div class="mt-2">
            <span class="badge text-bg-light text-muted">{{ $property->type->name ?? '' }}</span>
            <span class="badge text-bg-light text-muted">{{ $property->category->name ?? '' }}</span>
        </div>
    </div>
</div>
