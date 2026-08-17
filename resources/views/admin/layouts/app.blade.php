<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>

    @livewireStyles
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div
        class="d-flex ssm-admin-shell"
        id="admin-wrapper"
        x-data="{
            collapsed: localStorage.getItem('ssm-sidebar-collapsed') === '1',
            mobileOpen: false,
            toggleCollapse() {
                this.collapsed = !this.collapsed;
                localStorage.setItem('ssm-sidebar-collapsed', this.collapsed ? '1' : '0');
            },
            toggleMobile() { this.mobileOpen = !this.mobileOpen; },
        }"
        :class="{ 'is-collapsed': collapsed, 'is-mobile-open': mobileOpen }"
    >
        @include('admin.partials.sidebar')

        <div class="flex-grow-1 d-flex flex-column" style="min-height: 100vh; min-width: 0;">
            @include('admin.partials.topbar')

            <main class="p-4 flex-grow-1 bg-light">
                @yield('content')
            </main>
        </div>

        <div class="ssm-sidebar-backdrop d-lg-none" x-show="mobileOpen" x-cloak
            @click="mobileOpen = false" x-transition.opacity></div>
    </div>

    @include('partials.confirm-modal')
    @include('partials.toast-container')
    @include('partials.flash-to-toast')

    @stack('scripts')
</body>
</html>
