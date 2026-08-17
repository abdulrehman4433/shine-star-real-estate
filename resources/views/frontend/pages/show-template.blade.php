@extends('frontend.layouts.page-template')

@section('title', $page->seo_title)

@include('partials.seo-meta', ['seoable' => $page])

@section('content')
    @if (! $page->isPublished())
        <div class="alert alert-warning mb-0 text-center rounded-0">
            This page is a <strong>draft</strong> — only visible to you because you're a staff member.
        </div>
    @endif

    {{-- Template CSS --}}
    @if ($templateCss)
        <style>
            {!! $templateCss !!}
        </style>
    @endif

    {{-- Template HTML --}}
    {!! $templateHtml !!}

    {{-- Template JS --}}
    @if ($templateJs)
        <script>
            {!! $templateJs !!}
        </script>
    @endif
@endsection
