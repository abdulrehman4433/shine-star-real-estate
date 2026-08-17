@extends('frontend.layouts.app')

@section('title', 'My Dashboard')

@section('content')
    <div class="container py-5">
        <h1 class="h3 mb-4">Welcome, {{ auth()->user()->name }}</h1>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">My Favorites</h5>
                        <p class="card-text text-muted">{{ $favoritesCount }} saved {{ Str::plural('property', $favoritesCount) }}.</p>
                        <a href="{{ route('favorites.index') }}" class="btn btn-sm btn-primary">View Favorites</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">My Inquiries</h5>
                        <p class="card-text text-muted">You've sent {{ $inquiriesCount }} {{ Str::plural('inquiry', $inquiriesCount) }} so far.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
