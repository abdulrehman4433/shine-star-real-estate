@push('meta')
    @if ($seoable->seo_description)
        <meta name="description" content="{{ $seoable->seo_description }}">
    @endif
    @if ($seoable->seo_keywords)
        <meta name="keywords" content="{{ $seoable->seo_keywords }}">
    @endif

    <link rel="canonical" href="{{ $seoable->seo_canonical }}">

    <meta property="og:title" content="{{ $seoable->seo_title }}">
    @if ($seoable->seo_description)
        <meta property="og:description" content="{{ $seoable->seo_description }}">
    @endif
    @if ($seoable->seo_image)
        <meta property="og:image" content="{{ $seoable->seo_image }}">
    @endif
    <meta property="og:url" content="{{ $seoable->seo_canonical }}">

    <script type="application/ld+json">{!! json_encode(array_filter($seoable->seo_schema), JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
