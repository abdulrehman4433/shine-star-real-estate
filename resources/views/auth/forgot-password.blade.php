@extends('auth.layout')

@section('title', 'Forgot password')

@section('content')
    <p class="text-muted small">Enter your email and we'll send you a password reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">Email password reset link</button>

        <div class="text-center mt-3 small">
            <a href="{{ route('login') }}">Back to log in</a>
        </div>
    </form>
@endsection
