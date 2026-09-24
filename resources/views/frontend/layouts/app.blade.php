<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', \App\Models\Setting::get('site_name', config('app.name')))</title>

    @if ($favicon = \App\Models\Setting::getFileUrl('site_favicon'))
        <link rel="icon" href="{{ $favicon }}">
    @endif

    @stack('meta')
    @include('partials.tracking-head')

    {{-- Dynamic CDN assets: CSS, fonts, and header JS --}}
    @if (! empty($cdnHeaderAssets) && $cdnHeaderAssets->isNotEmpty())
        @foreach ($cdnHeaderAssets as $cdn)
            @if ($cdn->type === 'css')
                <link rel="stylesheet" href="{{ $cdn->url }}">
            @elseif ($cdn->type === 'font')
                <link rel="stylesheet" href="{{ $cdn->url }}">
            @elseif ($cdn->type === 'js')
                <script src="{{ $cdn->url }}" defer></script>
            @endif
        @endforeach
    @endif
    @livewireStyles
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @include('frontend.partials.header')

    <main class="flex-grow-1">
        @yield('content')
    </main>

    @include('frontend.partials.footer')

    {{-- Dynamic CDN assets: footer JS --}}
    @if (! empty($cdnFooterAssets) && $cdnFooterAssets->isNotEmpty())
        @foreach ($cdnFooterAssets as $cdn)
            @if ($cdn->type === 'js')
                <script src="{{ $cdn->url }}"></script>
            @endif
        @endforeach
    @endif

    @include('partials.confirm-modal')
    @include('partials.toast-container')
    @include('partials.flash-to-toast')

    @livewire('frontend.chat.chat-widget')

    @stack('scripts')
</body>
</html>
