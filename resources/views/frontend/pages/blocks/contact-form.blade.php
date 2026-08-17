@php $content = $block->content ?? []; @endphp

<section class="py-5 bg-light">
    <div class="container text-center" style="max-width: 600px;">
        @if (! empty($content['heading']))
            <h2 class="h3 mb-2">{{ $content['heading'] }}</h2>
        @endif
        @if (! empty($content['description']))
            <p class="text-muted mb-4">{{ $content['description'] }}</p>
        @endif
    </div>

    @livewire('frontend.pages.contact-form-block', ['page' => $block->page], key('contact-form-'.$block->id))
</section>
