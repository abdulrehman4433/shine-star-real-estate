<div class="container py-5">
    <h1 class="h3 mb-4">My Favorites</h1>

    @if ($properties->isEmpty())
        <div class="alert alert-info">You haven't saved any properties yet.</div>
    @else
        <div class="row g-4">
            @foreach ($properties as $property)
                <div class="col-md-4">
                    <div class="card h-100">
                        <a href="{{ route('properties.show', $property) }}">
                            @if ($property->featured_thumb_url)
                                <img src="{{ $property->featured_thumb_url }}" alt="{{ $property->title }}"
                                    class="card-img-top" style="height: 200px; object-fit: cover;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 200px;">
                                    No Image
                                </div>
                            @endif
                        </a>

                        <div class="card-body">
                            <h5 class="card-title mb-1">
                                <a href="{{ route('properties.show', $property) }}" class="text-decoration-none text-dark">
                                    {{ $property->title }}
                                </a>
                            </h5>
                            <p class="text-muted small mb-2">{{ $property->city }}</p>
                            <p class="fw-bold mb-3">{{ $property->formatted_price }}</p>

                            <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                @click="$store.confirm.open({ message: 'Remove \'{{ $property->title }}\' from your favorites?' }).then(ok => ok && $wire.unfavorite({{ $property->id }}))">
                                Remove from Favorites
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $properties->links() }}
        </div>
    @endif
</div>
