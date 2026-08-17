@php $content = $block->content ?? []; @endphp

<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="h3 mb-3">{{ $content['heading'] ?? '' }}</h2>
        @if (! empty($content['button_text']) && ! empty($content['button_url']))
            <a href="{{ $content['button_url'] }}" class="btn btn-light btn-lg">{{ $content['button_text'] }}</a>
        @endif
    </div>
</section>
