@extends('frontend.layouts.app')

@section('title', $page->seo_title)

@include('partials.seo-meta', ['seoable' => $page])

@section('content')
    @if (! $page->isPublished())
        <div class="alert alert-warning mb-0 text-center rounded-0">
            This page is a <strong>draft</strong> — only visible to you because you're a staff member.
        </div>
    @endif

    @include('frontend.pages.render-blocks', ['blocks' => $page->blocks])
@endsection
