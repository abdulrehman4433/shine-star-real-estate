@php $content = $block->content ?? []; @endphp

<section class="py-5">
    <div class="container" style="max-width: 800px;">
        @if (! empty($content['heading']))
            <h2 class="h3 mb-3">{{ $content['heading'] }}</h2>
        @endif
        <div style="white-space: pre-line;">{{ $content['body'] ?? '' }}</div>
    </div>
</section>
