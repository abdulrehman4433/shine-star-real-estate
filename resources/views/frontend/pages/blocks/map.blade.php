@php $content = $block->content ?? []; @endphp

<section class="py-5">
    <div class="container">
        @if (! empty($content['label']))
            <h2 class="h4 mb-3">{{ $content['label'] }}</h2>
        @endif

        <div
            wire:ignore
            wire:key="map-block-{{ $block->id }}-{{ $content['lat'] ?? 0 }}-{{ $content['lng'] ?? 0 }}-{{ $content['zoom'] ?? 12 }}"
            x-data="{
                init() {
                    const map = L.map($refs.map, { scrollWheelZoom: false }).setView([{{ $content['lat'] ?? 20 }}, {{ $content['lng'] ?? 0 }}], {{ $content['zoom'] ?? 12 }});
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    }).addTo(map);
                    L.marker([{{ $content['lat'] ?? 20 }}, {{ $content['lng'] ?? 0 }}]).addTo(map);
                }
            }"
        >
            <div x-ref="map" style="height: 320px;" class="rounded"></div>
        </div>
    </div>
</section>
