@extends('frontend.layouts.app')

@section('title', 'Home - ' . config('app.name'))

@section('content')
    <div class="container py-5">
        <div class="p-5 mb-4 bg-light rounded-3">
            <h1 class="display-6 fw-bold">Welcome to {{ config('app.name') }}</h1>
            <p class="col-md-8 fs-5">
                This is dummy frontend content confirming the base layout, Bootstrap 5, and Alpine.js are wired up correctly.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Buy</h5>
                        <p class="card-text">Browse properties for sale (coming in Module 3).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Rent</h5>
                        <p class="card-text">Browse rental listings (coming in Module 3).</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Commercial</h5>
                        <p class="card-text">Browse commercial listings (coming in Module 3).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {!! $featuredPropertiesHtml !!}
    {!! $reviewsHtml !!}
@endsection
