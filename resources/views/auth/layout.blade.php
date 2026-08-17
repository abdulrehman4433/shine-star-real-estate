@php
    $authSiteName = \App\Models\Setting::get('site_name', config('app.name'));
    $authLogo = \App\Models\Setting::getFileUrl('site_logo')
        ?: (is_file(public_path('storage/2/shine-star-marketing.png'))
            ? asset('storage/2/shine-star-marketing.png')
            : null);
    // Decorative real-estate image for the left panel (falls back to the brand gradient if missing).
    $authMediaImage = is_file(public_path('storage/2/p-6.jpg'))
        ? asset('storage/2/p-6.jpg')
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') - {{ config('app.name') }}</title>

    @livewireStyles
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="ssm-auth">
        {{-- Left: brand / media panel --}}
        <div class="ssm-auth__media">
            @if ($authMediaImage)
                <div class="ssm-auth__media-bg" style="background-image: url('{{ $authMediaImage }}');" role="img"
                    aria-label="{{ $authSiteName }}"></div>
            @endif
            <div class="ssm-auth__media-overlay"></div>

            <div class="ssm-auth__media-content">
                <div class="ssm-auth__brand">
                    @if ($authLogo)
                        <img src="{{ $authLogo }}" alt="{{ $authSiteName }}" class="ssm-auth__brand-logo">
                    @else
                        <span class="ssm-auth__brand-text">{{ $authSiteName }}</span>
                    @endif
                </div>

                <div class="ssm-auth__media-copy">
                    <h1 class="ssm-auth__headline">Find your perfect property, from anywhere.</h1>
                    <p class="ssm-auth__tagline">
                        Browse every active listing, manage your own properties, and connect with owners — all in one place.
                    </p>
                    <ul class="ssm-auth__features">
                        <li><i class="bi bi-check-circle-fill"></i> Browse every active listing</li>
                        <li><i class="bi bi-check-circle-fill"></i> List &amp; manage your own property</li>
                        <li><i class="bi bi-check-circle-fill"></i> Receive inquiries instantly</li>
                    </ul>
                </div>

                <div class="ssm-auth__media-footer">
                    &copy; {{ date('Y') }} {{ $authSiteName }}. All rights reserved.
                </div>
            </div>
        </div>

        {{-- Right: form panel --}}
        <div class="ssm-auth__form">
            <div class="ssm-auth__form-inner">
                <div class="ssm-auth__mobile-brand d-lg-none">
                    @if ($authLogo)
                        <img src="{{ $authLogo }}" alt="{{ $authSiteName }}" class="ssm-auth__brand-logo">
                    @else
                        <span class="ssm-auth__brand-text">{{ $authSiteName }}</span>
                    @endif
                </div>

                <h2 class="ssm-auth__form-title">@yield('title')</h2>
                <p class="ssm-auth__form-subtitle">
                    @yield('subtitle', 'Enter your details below to continue.')
                </p>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
