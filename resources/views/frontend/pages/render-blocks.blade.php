@foreach ($blocks as $block)
    @include('frontend.pages.blocks.'.$block->type, ['block' => $block])
@endforeach
