@extends('frontend.layouts.app')

@section('title', 'Agent Dashboard')

@section('content')
    <div class="container py-5">
        <h1 class="h3 mb-4">Welcome, {{ auth()->user()->name }}</h1>

        @if (auth()->user()->hasRole('agency'))
            <p class="text-muted">Agency: {{ auth()->user()->agency_name }}</p>
        @endif

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">My Listings</h5>
                        <p class="card-text text-muted">Manage your property listings.</p>
                        <a href="{{ route('agent.listings.index') }}" class="btn btn-sm btn-primary">View Listings</a>
                        <a href="{{ route('agent.listings.create') }}" class="btn btn-sm btn-outline-primary">+ New Listing</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Leads</h5>
                        <p class="card-text text-muted">Track and follow up with your assigned leads.</p>
                        <a href="{{ route('agent.leads.index') }}" class="btn btn-sm btn-primary">View Leads</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
