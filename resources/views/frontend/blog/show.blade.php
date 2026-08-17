@extends('frontend.layouts.app')

@section('title', $post->seo_title)

@include('partials.seo-meta', ['seoable' => $post])

@section('content')
    <div class="container py-5" style="max-width: 800px;">
        @if (! $post->isPublished())
            <div class="alert alert-warning">
                This post is <strong>{{ ucfirst($post->status) }}</strong> and is only visible to you because
                you're a staff member.
            </div>
        @endif

        @if ($post->category)
            <span class="badge text-bg-light text-muted mb-2">{{ $post->category->name }}</span>
        @endif

        <h1 class="h3">{{ $post->title }}</h1>
        <p class="text-muted small">
            {{ $post->author->name }} &middot; {{ $post->published_at?->format('F j, Y') ?? 'Not yet published' }}
        </p>

        @include('partials.share-buttons', ['shareUrl' => url()->current(), 'shareTitle' => $post->title])

        @if ($post->featured_image_url)
            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-100 rounded mb-4" style="max-height: 420px; object-fit: cover;">
        @endif

        <div class="mb-4">{!! $post->content !!}</div>

        @if ($post->tags->isNotEmpty())
            <div class="mb-4">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('blog.index', ['tag' => $tag->id]) }}" class="badge text-bg-light text-muted text-decoration-none me-1">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($relatedPosts->isNotEmpty())
            <hr class="my-4">
            <h2 class="h5 mb-3">Related Posts</h2>
            <div class="row g-4">
                @foreach ($relatedPosts as $related)
                    <div class="col-md-4">
                        <a href="{{ route('blog.show', $related) }}" class="text-decoration-none text-dark">
                            @if ($related->featured_thumb_url)
                                <img src="{{ $related->featured_thumb_url }}" class="w-100 rounded mb-2" style="height: 140px; object-fit: cover;">
                            @endif
                            <h6>{{ $related->title }}</h6>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
