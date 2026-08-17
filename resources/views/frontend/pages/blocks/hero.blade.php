@php $content = $block->content ?? []; @endphp

<section class="py-5 text-center {{ $block->hero_image_url ? 'text-white' : 'bg-light' }}"
    style="{{ $block->hero_image_url ? 'background: url('.$block->hero_image_url.') center/cover; min-height: 360px; display: flex; align-items: center; justify-content: center;' : '' }}">
    <div class="container">
        <h1 class="display-5 fw-bold">{{ $content['heading'] ?? '' }}</h1>
        @if (! empty($content['subheading']))
            <p class="fs-5">{{ $content['subheading'] }}</p>
        @endif
        @if (! empty($content['button_text']) && ! empty($content['button_url']))
            <a href="{{ $content['button_url'] }}" class="btn btn-primary btn-lg mt-2">{{ $content['button_text'] }}</a>
        @endif
    </div>
</section>
