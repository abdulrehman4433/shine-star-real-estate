{{-- Trusted, first-party partial — see menu.blade.php for why this isn't stored in the DB. --}}
<div class="ssm-header__search" x-data="{ searchOpen: false }">
    <button class="ssm-header__search-toggle" @click="searchOpen = !searchOpen" :aria-expanded="searchOpen" aria-label="Toggle search">
        <svg class="ssm-header__search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
        </svg>
    </button>
    <form action="{{ url('/properties') }}" method="GET"
          class="ssm-header__search-form"
          x-show="searchOpen"
          x-transition:enter="ssm-search-enter"
          x-transition:enter-start="ssm-search-enter-start"
          x-transition:enter-end="ssm-search-enter-end"
          @click.away="searchOpen = false"
          @keydown.escape="searchOpen = false"
          x-cloak>
        <input type="text" name="search" class="ssm-header__search-input" placeholder="Search properties..."
               aria-label="Search properties" x-ref="searchInput">
        <button type="submit" class="ssm-header__search-submit" aria-label="Submit search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 21l-4.35-4.35"/>
                <circle cx="11" cy="11" r="8"/>
            </svg>
        </button>
    </form>
</div>
