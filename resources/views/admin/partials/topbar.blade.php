<nav class="navbar ssm-topbar" x-data="{ open: false }">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="ssm-topbar__menu-btn d-lg-none" @click="toggleMobile()" aria-label="Toggle menu">
            <i class="bi bi-list" style="font-size: 1.2rem;"></i>
        </button>
        <span class="ssm-topbar__title">@yield('title', 'Dashboard')</span>
    </div>

    <div class="ms-auto d-flex align-items-center position-relative">
        <a href="{{ url('/') }}" class="ssm-topbar__site-btn me-2" target="_blank" rel="noopener"
            title="View website" aria-label="View website">
            <i class="bi bi-globe2"></i>
        </a>

        @livewire('admin.notifications.bell')

        <button class="ssm-user-btn" @click="open = !open">
            <span class="ssm-user-avatar">
                {{ auth()->check() ? Str::substr(auth()->user()->name, 0, 1) : '?' }}
            </span>
            <span class="d-none d-sm-inline">{{ auth()->check() ? auth()->user()->name : 'Guest' }}</span>
            <i class="bi bi-chevron-down small"></i>
        </button>

        <div class="dropdown-menu dropdown-menu-end shadow-sm p-2" :class="{ 'show': open }" style="right: 0; left: auto;">
            <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
            </form>
        </div>
    </div>
</nav>
