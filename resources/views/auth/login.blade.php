@extends('auth.layout')

@section('title', 'Log in')

@section('subtitle', 'Welcome back — please enter your details to continue.')

@section('content')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="password" class="form-label mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
            </div>
            <div class="position-relative" x-data="{ showPassword: false }">
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                    autocomplete="current-password"
                    class="form-control pe-5 @error('password') is-invalid @enderror">
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y p-0 me-3 text-muted"
                    style="z-index: 5;" tabindex="-1" aria-label="Toggle password visibility"
                    @click="showPassword = !showPassword">
                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Log in</button>

        <div class="ssm-auth__link-row">
            <span class="text-muted small">Don't have an account?</span>
            <a href="{{ route('register') }}" class="fw-semibold">Create one free</a>
        </div>

        <div class="ssm-auth__secure-note">
            <i class="bi bi-shield-lock"></i> Your account is protected with secure login
        </div>
    </form>
@endsection
