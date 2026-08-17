{{-- Trusted, first-party partial — NOT stored in the database. Header templates never contain
     @foreach/@if directives themselves (see HeaderTemplate::safeSubstitute()); this is where the
     actual menu tree is rendered, then handed to the stored template as one pre-rendered {!! $menuHtml !!} string. --}}
<ul class="ssm-header__menu" role="menubar">
    @foreach ($menuTree as $node)
        @php $item = $node['item']; @endphp
        @if (empty($node['children']))
            <li class="ssm-header__menu-item" role="none">
                <a href="{{ $item->resolvedUrl() }}"
                   class="ssm-header__menu-link {{ request()->fullUrlIs($item->resolvedUrl()) || request()->url() === $item->resolvedUrl() ? 'is-active' : '' }}"
                   target="{{ $item->target }}"
                   role="menuitem"
                   {{ request()->fullUrlIs($item->resolvedUrl()) ? 'aria-current="page"' : '' }}>
                    {{ $item->label }}
                </a>
            </li>
        @else
            <li class="ssm-header__menu-item ssm-header__menu-item--has-dropdown"
                role="none"
                x-data="{ dropdownOpen: false }"
                @mouseenter="dropdownOpen = true"
                @mouseleave="dropdownOpen = false"
                @keydown.escape="dropdownOpen = false">
                <a href="{{ $item->resolvedUrl() }}"
                   class="ssm-header__menu-link ssm-header__dropdown-toggle"
                   role="menuitem"
                   aria-haspopup="true"
                   :aria-expanded="dropdownOpen"
                   @click.prevent="dropdownOpen = !dropdownOpen">
                    {{ $item->label }}
                    <svg class="ssm-header__chevron" width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                        <path d="M2.5 3.75L5 6.25L7.5 3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <ul class="ssm-header__dropdown"
                    role="menu"
                    x-show="dropdownOpen"
                    x-transition:enter="ssm-dropdown-enter"
                    x-transition:enter-start="ssm-dropdown-enter-start"
                    x-transition:enter-end="ssm-dropdown-enter-end"
                    x-transition:leave="ssm-dropdown-leave"
                    x-transition:leave-start="ssm-dropdown-leave-start"
                    x-transition:leave-end="ssm-dropdown-leave-end"
                    @click.away="dropdownOpen = false"
                    x-cloak>
                    @foreach ($node['children'] as $child)
                        <li role="none">
                            <a href="{{ $child['item']->resolvedUrl() }}"
                               class="ssm-header__dropdown-link"
                               target="{{ $child['item']->target }}"
                               role="menuitem"
                               tabindex="0">
                                {{ $child['item']->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
    @endforeach
</ul>
