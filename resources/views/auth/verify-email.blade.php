@extends('auth.layout')

@section('title', 'Verify your email')

@section('content')
    <p class="text-muted small">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we
        just emailed to you. If you didn't receive the email, we will gladly send you another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to the email address you provided during registration.
        </div>
    @endif

    <div class="d-flex justify-content-between mt-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Log out</button>
        </form>
    </div>
@endsection
