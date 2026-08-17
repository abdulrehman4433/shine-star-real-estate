@extends('frontend.layouts.app')

@section('title', 'Projects - ' . config('app.name'))

@section('content')
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="h3">Our Projects</h1>
            <p class="text-muted">Explore our real estate developments, from residential societies to commercial projects.</p>
        </div>

        @if ($projects->isEmpty())
            <p class="text-muted text-center">No projects available right now. Please check back soon.</p>
        @else
            <div class="row g-4">
                @foreach ($projects as $project)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div style="height: 200px; overflow: hidden; background: #f1f3f5;">
                                @if ($project->cover_thumb_url)
                                    <img src="{{ $project->cover_thumb_url }}" alt="{{ $project->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">No Image</div>
                                @endif
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge text-bg-primary">{{ $project->typeEnum()->label() }}</span>
                                </div>
                                <h5 class="card-title">
                                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none">{{ $project->title }}</a>
                                </h5>
                                <p class="text-muted small mb-2">{{ $project->society_name }}</p>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-geo-alt"></i> {{ $project->city ?: '—' }}
                                    &middot;
                                    <i class="bi bi-grid-3x3-gap"></i> {{ $project->blocks_count }} Blocks
                                    &middot;
                                    <i class="bi bi-rulers"></i> {{ $project->plot_sizes_count }} Options
                                </p>
                                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-primary mt-auto">View Details</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
