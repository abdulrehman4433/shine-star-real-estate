{{-- Trusted, first-party partial — see menu.blade.php for why this isn't stored in the DB. --}}
<div class="ssm-header__cta">
    @auth
        <a href="{{ auth()->user()->dashboardRoute() }}" class="ssm-header__cta-btn ssm-header__cta-btn--primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right: 6px;">
                <rect x="3" y="3" width="7" height="7"/>
                <rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/>
                <rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" class="ssm-header__cta-btn ssm-header__cta-btn--outline">{{ $ctaLoginLabel }}</a>
        <a href="{{ route('register') }}" class="ssm-header__cta-btn ssm-header__cta-btn--primary">{{ $ctaRegisterLabel }}</a>
    @endauth
</div>
