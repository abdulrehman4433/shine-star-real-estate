@if ($activeHeaderTemplate && $activeHeaderTemplate->status === 'published')
    {{-- Active Header Template --}}
    @if ($headerTemplateCss)
        <style id="ssm-header-styles">
            {!! $headerTemplateCss !!}
        </style>
    @endif

    {!! $headerTemplateHtml !!}

    @if ($headerTemplateJs)
        <script id="ssm-header-script">
            document.addEventListener('DOMContentLoaded', function() {
                {!! $headerTemplateJs !!}
            });
        </script>
    @endif
@else
    {{-- Fallback: Default Bootstrap Header --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom" x-data="{ open: false }">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}">
                @if ($siteLogoUrl)
                    <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" style="height: 32px;">
                @else
                    {{ $siteName }}
                @endif
            </a>

            <button class="navbar-toggler" type="button" @click="open = !open" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" :class="{ 'show': open }">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    @forelse ($headerMenuTree as $node)
                        @php $item = $node['item']; @endphp
                        @if (empty($node['children']))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->fullUrlIs($item->resolvedUrl()) ? 'active fw-semibold' : '' }}"
                                   href="{{ $item->resolvedUrl() }}" target="{{ $item->target }}">
                                    {{ $item->label }}
                                </a>
                            </li>
                        @else
                            <li class="nav-item dropdown" x-data="{ menuOpen: false }" @click.outside="menuOpen = false">
                                <a class="nav-link dropdown-toggle" href="#" role="button" @click.prevent="menuOpen = !menuOpen">
                                    {{ $item->label }}
                                </a>
                                <ul class="dropdown-menu" :class="{ 'show': menuOpen }">
                                    @foreach ($node['children'] as $child)
                                        <li>
                                            <a class="dropdown-item" href="{{ $child['item']->resolvedUrl() }}"
                                               target="{{ $child['item']->target }}">
                                                {{ $child['item']->label }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @empty
                        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/properties') }}">Properties</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/blog') }}">Blog</a></li>
                    @endforelse
                </ul>

                <ul class="navbar-nav">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ auth()->user()->dashboardRoute() }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('favorites.index') }}">Favorites</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('chat.index') }}">Messages</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile.edit') }}">Profile</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link p-0">Log out</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Log in</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth

                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-2 my-1" href="{{ route('my.properties.index') }}">
                            <i class="bi bi-plus-lg me-1"></i>Add Property
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
@endif
