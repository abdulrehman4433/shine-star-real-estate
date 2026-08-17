@extends('auth.layout')

@section('title', 'Create your account')

@section('subtitle', 'Create a free guest account to browse and list properties.')

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Every public registration creates a guest account — the role is fixed, not user-selectable. --}}
        <input type="hidden" name="role" value="{{ \App\Enums\RoleName::Guest->value }}">

        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="form-control @error('name') is-invalid @enderror">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel"
                class="form-control @error('phone') is-invalid @enderror">
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="position-relative" x-data="{ showPassword: false }">
                <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                    autocomplete="new-password"
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

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <div class="position-relative" x-data="{ showPassword: false }">
                <input id="password_confirmation" :type="showPassword ? 'text' : 'password'"
                    name="password_confirmation" required autocomplete="new-password" class="form-control pe-5">
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y p-0 me-3 text-muted"
                    style="z-index: 5;" tabindex="-1" aria-label="Toggle password visibility"
                    @click="showPassword = !showPassword">
                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Create Account</button>

        <div class="ssm-auth__link-row">
            <span class="text-muted small">Already have an account?</span>
            <a href="{{ route('login') }}" class="fw-semibold">Log in</a>
        </div>

        <div class="ssm-auth__secure-note">
            <i class="bi bi-shield-check"></i> Your details are safe with us
        </div>
    </form>
@endsection
