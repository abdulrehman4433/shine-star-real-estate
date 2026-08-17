@php $images = $block->getMedia('gallery'); @endphp

<section class="py-5">
    <div class="container">
        @if ($images->isEmpty())
            <p class="text-muted text-center mb-0">No gallery images yet.</p>
        @else
            <div class="row g-3">
                @foreach ($images as $image)
                    <div class="col-md-4 col-6">
                        <img src="{{ $image->getUrl() }}" class="w-100 rounded" style="height: 220px; object-fit: cover;">
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
