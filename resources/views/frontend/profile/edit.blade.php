@extends('frontend.layouts.app')

@section('title', 'Edit Profile')

@section('content')
    <div class="container py-5" style="max-width: 640px;">
        <h1 class="h3 mb-4">Edit Profile</h1>

        @if (session('status') === 'profile-updated')
            <div class="alert alert-success">Profile updated successfully.</div>
        @endif

        @if ($errors->updateProfileInformation->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->updateProfileInformation->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" x-data="{ role: '{{ $user->getRoleNames()->first() }}' }">
            @csrf
            @method('PUT')

            <div class="mb-3 d-flex align-items-center gap-3">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="rounded-circle" width="64" height="64">
                @endif
                <div class="flex-grow-1">
                    <label for="photo" class="form-label">Avatar</label>
                    <input id="photo" type="file" name="photo" accept="image/*" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="form-control">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="form-control">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="form-control">
            </div>

            @if ($user->hasRole('agent') || $user->hasRole('agency'))
                <hr>
                <h2 class="h6">Agency Info</h2>

                <div class="mb-3">
                    <label for="agency_name" class="form-label">Agency name</label>
                    <input id="agency_name" type="text" name="agency_name" value="{{ old('agency_name', $user->agency_name) }}"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label for="agency_license_no" class="form-label">Agency license no.</label>
                    <input id="agency_license_no" type="text" name="agency_license_no" value="{{ old('agency_license_no', $user->agency_license_no) }}"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label for="agency_address" class="form-label">Agency address</label>
                    <input id="agency_address" type="text" name="agency_address" value="{{ old('agency_address', $user->agency_address) }}"
                        class="form-control">
                </div>
            @endif

            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
    </div>
@endsection
