{{-- Trusted, first-party partial — see menu.blade.php for why this isn't stored in the DB. --}}
@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="ssm-header__logo"
         style="width: auto; max-width: var(--logo-w, 200px);">
@else
    <span class="ssm-header__brand-text">{{ $siteName }}</span>
@endif
