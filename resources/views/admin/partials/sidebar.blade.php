            @php
                $crmActive = request()->routeIs('admin.leads.*') || request()->routeIs('admin.chat.*') || request()->routeIs('admin.contact-messages.*');
                $cmsActive = request()->routeIs('admin.pages.*') || request()->routeIs('admin.menus.*') || request()->routeIs('admin.headers.*') || request()->routeIs('admin.footer.*') || request()->routeIs('admin.cdn.*') || request()->routeIs('admin.reviews.*');
            @endphp

<aside class="ssm-sidebar" :class="{ 'is-mobile-open': mobileOpen }">
    <div class="ssm-sidebar__brand">
        <a href="{{ url('/admin') }}" class="ssm-sidebar__brand-link">
            <span class="ssm-sidebar__brand-mark">{{ Str::substr(config('app.name'), 0, 1) }}</span>
            <span class="ssm-sidebar__brand-text">{{ config('app.name') }}</span>
        </a>
        <button type="button" class="ssm-sidebar__collapse-btn d-none d-lg-inline-flex" @click="toggleCollapse()"
            title="Collapse sidebar">
            <i class="bi" :class="collapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'"></i>
        </button>
    </div>

    <nav class="ssm-sidebar__nav">
        <ul class="ssm-nav">
            <li>
                <a href="{{ route('admin.dashboard') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                    title="Dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span class="ssm-nav__label">Dashboard</span>
                </a>
            </li>
            <li>
                <button type="button" class="ssm-nav__toggle {{ $cmsActive ? 'is-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#cms-submenu"
                    aria-expanded="{{ $cmsActive ? 'true' : 'false' }}" title="CMS">
                    <i class="bi bi-files ssm-nav__icon"></i>
                    <span class="ssm-nav__label">CMS</span>
                    <i class="bi bi-chevron-down ssm-nav__toggle-chevron"></i>
                </button>
                <div class="collapse {{ $cmsActive ? 'show' : '' }}" id="cms-submenu">
                    <ul class="ssm-nav__submenu">
                        <li>
                            <a href="{{ route('admin.pages.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.pages.*') ? 'is-active' : '' }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span class="ssm-nav__label">Pages</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.menus.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.menus.*') ? 'is-active' : '' }}">
                                <i class="bi bi-list-ul"></i>
                                <span class="ssm-nav__label">Menus</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.headers.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.headers.*') ? 'is-active' : '' }}">
                                <i class="bi bi-layout-text-window-reverse"></i>
                                <span class="ssm-nav__label">Header</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.footer.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.footer.*') ? 'is-active' : '' }}">
                                <i class="bi bi-layout-text-window-reverse"></i>
                                <span class="ssm-nav__label">Footer</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.reviews.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.reviews.*') ? 'is-active' : '' }}">
                                <i class="bi bi-chat-quote"></i>
                                <span class="ssm-nav__label">Reviews</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.cdn.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.cdn.*') ? 'is-active' : '' }}">
                                <i class="bi bi-link-45deg"></i>
                                <span class="ssm-nav__label">CDN</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li>
                <a href="{{ route('admin.properties.index') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.properties.*') ? 'is-active' : '' }}"
                    title="Properties">
                    <i class="bi bi-houses"></i>
                    <span class="ssm-nav__label">Properties</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.projects.index') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.projects.*') ? 'is-active' : '' }}"
                    title="Projects">
                    <i class="bi bi-buildings"></i>
                    <span class="ssm-nav__label">Projects</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.blog.posts.index') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.blog.posts.*') ? 'is-active' : '' }}"
                    title="Blog Posts">
                    <i class="bi bi-newspaper"></i>
                    <span class="ssm-nav__label">Blog Posts</span>
                </a>
            </li>
            <li>
                <button type="button" class="ssm-nav__toggle {{ $crmActive ? 'is-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#crm-submenu"
                    aria-expanded="{{ $crmActive ? 'true' : 'false' }}" title="CRM">
                    <i class="bi bi-people ssm-nav__icon"></i>
                    <span class="ssm-nav__label">CRM</span>
                    <i class="bi bi-chevron-down ssm-nav__toggle-chevron"></i>
                </button>
                <div class="collapse {{ $crmActive ? 'show' : '' }}" id="crm-submenu">
                    <ul class="ssm-nav__submenu">
                        <li>
                            <a href="{{ route('admin.leads.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.leads.*') ? 'is-active' : '' }}">
                                <i class="bi bi-person-lines-fill"></i>
                                <span class="ssm-nav__label">Leads</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.chat.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.chat.*') ? 'is-active' : '' }}">
                                <i class="bi bi-chat-dots"></i>
                                <span class="ssm-nav__label">Chat</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.contact-messages.index') }}" @click="mobileOpen = false"
                                class="ssm-nav__link {{ request()->routeIs('admin.contact-messages.*') ? 'is-active' : '' }}">
                                <i class="bi bi-envelope"></i>
                                <span class="ssm-nav__label">Contact Messages</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li>
                <a href="{{ route('admin.settings.index') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.settings.index') ? 'is-active' : '' }}"
                    title="Settings">
                    <i class="bi bi-gear"></i>
                    <span class="ssm-nav__label">Settings</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings.seo') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.settings.seo') ? 'is-active' : '' }}"
                    title="SEO Settings">
                    <i class="bi bi-search"></i>
                    <span class="ssm-nav__label">SEO Settings</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings.backup') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.settings.backup') ? 'is-active' : '' }}"
                    title="Backup &amp; Migration">
                    <i class="bi bi-database-fill-down"></i>
                    <span class="ssm-nav__label">Backup &amp; Migration</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings.system-check') }}" @click="mobileOpen = false"
                    class="ssm-nav__link {{ request()->routeIs('admin.settings.system-check') ? 'is-active' : '' }}"
                    title="System Requirements Check">
                    <i class="bi bi-clipboard2-check"></i>
                    <span class="ssm-nav__label">System Check</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
