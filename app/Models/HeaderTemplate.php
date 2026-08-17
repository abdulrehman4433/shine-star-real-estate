<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HeaderTemplate extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'status',
        'is_active',
        'html',
        'css',
        'js',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('header_logo')->singleFile();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** URL of this template's custom logo, or null if it uses the site-wide logo.
     *
     * Returns a relative path (e.g. /storage/1/logo.png) instead of an absolute URL
     * so images work regardless of the APP_URL or subdirectory deployment.
     */
    public function logoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('header_logo');

        if ($url) {
            // Strip scheme + host to get a relative path that works with any domain
            $url = parse_url($url, PHP_URL_PATH);
        }

        return $url ?: null;
    }

    /** Get the currently active header template (cached). */
    public static function activeTemplate(): ?self
    {
        return Cache::remember('header_template.active', now()->addHour(), function () {
            return static::where('is_active', true)->where('status', 'published')->first();
        });
    }

    /** Forget the active header cache — call after any write that could change which template is active. */
    public static function forgetActiveCache(): void
    {
        Cache::forget('header_template.active');
    }

    /**
     * Parse settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        return array_merge(static::defaultSettings(), $this->settings ?? []);
    }

    public static function defaultSettings(): array
    {
        return [
            'sticky' => true,
            'transparent' => false,
            'header_height' => 80,
            'header_bg_color' => '#ffffff',
            'header_text_color' => '#1f2937',
            'header_border_color' => '#e5e7eb',
            'dropdown_hover_style' => 'fade',
            'menu_position' => 'right',
            'show_cta' => true,
            'cta_login_label' => 'Login',
            'cta_register_label' => 'Register',
            'cta_contact_label' => 'Contact Us',
            'cta_contact_url' => '/contact',
            'show_search' => false,
            'font_family' => "'Inter', system-ui, -apple-system, sans-serif",
            'nav_font_size' => 14,
            'nav_font_weight' => 500,
            'nav_item_padding_x' => 16,
            'nav_item_padding_y' => 8,
            'logo_height' => 36,
            'logo_width' => 140,
            'border_radius' => 8,
            'shadow_enabled' => true,
            'shadow_color' => 'rgba(0,0,0,0.08)',
            'mobile_breakpoint' => 991,
        ];
    }

    /**
     * Render the stored HTML template.
     *
     * Deliberately does NOT use Blade::render() — compiling and executing an arbitrary string
     * pulled from a database column is full PHP code execution from stored data (a real RCE risk:
     * anything from a compromised admin account to a pasted import-JSON payload could plant
     * `@php ... @endphp` or an arbitrary method call). Instead, the menu/CTA/search/logo blocks are
     * rendered ahead of time (via Blade partials or inline PHP string building — never from
     * admin-stored content), and the stored template only ever gets a single, restricted substitution
     * pass: `{{ $var }}` (escaped) and `{!! $var !!}` (raw, for the pre-rendered HTML blocks)
     * referencing a plain variable name. No directive, method call, or arbitrary expression can
     * execute — see safeSubstitute() below.
     */
    public function renderHtml(): string
    {
        if (empty($this->html)) {
            return '';
        }

        $settings = $this->getSettingsWithDefaults();
        $menuTree = Menu::cachedTree('header');
        $siteName = Setting::get('site_name', config('app.name'));
        $logoUrl = $this->logoUrl() ?: Setting::getFileUrl('site_logo');

        $data = [
            'siteName' => $siteName,
            'homeUrl' => url('/'),
            'headerClasses' => $this->headerClasses(),
            // Default template pre-rendered blocks (from trusted Blade partials)
            'logoHtml' => view('partials.header-template.logo', compact('logoUrl', 'siteName'))->render(),
            'menuHtml' => view('partials.header-template.menu', compact('menuTree'))->render(),
            'searchHtml' => $settings['show_search']
                ? view('partials.header-template.search')->render()
                : '',
            'ctaHtml' => $settings['show_cta']
                ? view('partials.header-template.cta', [
                    'ctaLoginLabel' => $settings['cta_login_label'],
                    'ctaRegisterLabel' => $settings['cta_register_label'],
                ])->render()
                : '',
            // Resido-style custom template blocks (built inline in PHP — no file-based partials)
            'residoMenuHtml' => static::buildResidoMenuHtml($menuTree),
            'residoActionsHtml' => static::buildResidoActionsHtml(),
            // Bootstrap-navbar Resido variant (the live "Shine Star Premium" template's actual
            // markup — plain Bootstrap .navbar/.dropdown classes, not the ssm-header__ system above)
            'logoBlockHtml' => static::buildResidoLogoHtml($logoUrl, $siteName),
            'navMenuHtml' => static::buildResidoBootstrapMenuHtml($menuTree),
            'headerActionsHtml' => static::buildResidoActionsBlockHtml(),
        ];

        return $this->safeSubstitute($this->html, $data);
    }

    /**
     * Bootstrap-navbar variant of the logo block — matches the `.resido-logo`/`.resido-logo-mark`/
     * `.resido-logo-text` classes the live "Shine Star Premium" template's stored HTML/CSS use
     * (a different DOM shape than logo.blade.php's `.ssm-header__logo`). Shows the real uploaded
     * logo (template-specific first, falling back to the site-wide Settings logo — same precedence
     * as everywhere else) when one exists; otherwise falls back to the original decorative SVG mark
     * plus the dynamic site name (never the hardcoded "Resido" text this replaced).
     */
    public static function buildResidoLogoHtml(?string $logoUrl, string $siteName): string
    {
        $homeUrl = e(url('/'));
        $siteNameEsc = e($siteName);

        if ($logoUrl) {
            return '<a class="resido-logo" href="'.$homeUrl.'">'
                .'<img src="'.e($logoUrl).'" alt="'.$siteNameEsc.'" class="resido-logo-img">'
                .'</a>';
        }

        return '<a class="resido-logo" href="'.$homeUrl.'">'
            .'<span class="resido-logo-mark"><svg viewBox="0 0 40 44" xmlns="http://www.w3.org/2000/svg">'
            .'<polygon points="20,0 40,11 40,33 20,44 20,30 30,24.5 30,19.5 20,14" fill="#274ac2"/>'
            .'<polygon points="20,0 0,11 0,33 20,44 20,30 10,24.5 10,19.5 20,14" fill="#7fb2ff"/>'
            .'<rect x="17.5" y="20" width="5" height="16" fill="#1c2340"/>'
            .'</svg></span>'
            .'<span class="resido-logo-text">'.$siteNameEsc.'</span>'
            .'</a>';
    }

    /**
     * Bootstrap-navbar variant of the menu — renders plain `<li class="nav-item">`/
     * `<li class="nav-item dropdown">` + `data-bs-toggle="dropdown"` markup (matches the live
     * template's `.resido-nav`/`.dropdown-menu` CSS and relies on Bootstrap's own bundled JS for
     * the dropdown/collapse behavior — no custom Alpine/JS needed), instead of the `ssm-header__`
     * markup `buildResidoMenuHtml()` produces above.
     */
    public static function buildResidoBootstrapMenuHtml(array $menuTree): string
    {
        if (empty($menuTree)) {
            $homeUrl = e(url('/'));
            $propsUrl = e(url('/properties'));
            $blogUrl = e(url('/blog'));

            return '<li class="nav-item"><a class="nav-link" href="'.$homeUrl.'">Home</a></li>'
                .'<li class="nav-item"><a class="nav-link" href="'.$propsUrl.'">Properties</a></li>'
                .'<li class="nav-item"><a class="nav-link" href="'.$blogUrl.'">Blog</a></li>';
        }

        $html = '';

        foreach ($menuTree as $node) {
            $item = $node['item'];
            $children = $node['children'] ?? [];
            $url = e($item->resolvedUrl());
            $label = e($item->label);
            $target = e($item->target);

            if (empty($children)) {
                $html .= '<li class="nav-item"><a class="nav-link" href="'.$url.'" target="'.$target.'">'.$label.'</a></li>';

                continue;
            }

            $html .= '<li class="nav-item dropdown">';
            $html .= '<a class="nav-link dropdown-toggle" href="'.$url.'" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
            $html .= $label.' <i class="bi bi-chevron-down"></i></a>';
            $html .= '<ul class="dropdown-menu">';

            foreach ($children as $child) {
                $childItem = $child['item'];
                $childUrl = e($childItem->resolvedUrl());
                $childLabel = e($childItem->label);
                $childTarget = e($childItem->target);
                $html .= '<li><a class="dropdown-item" href="'.$childUrl.'" target="'.$childTarget.'">'.$childLabel.'</a></li>';
            }

            $html .= '</ul></li>';
        }

        return $html;
    }

    /**
     * Bootstrap-navbar variant of the right-side actions block — just the "Add Property" link,
     * matching the live template's `.add-property-link` class (a different DOM shape than
     * `buildResidoActionsHtml()`'s `.ssm-header__add-property`). No "Sign In"/"Dashboard" button —
     * both were removed per explicit request. The link always points at the `auth`-protected
     * `/my/properties` hub: a logged-in user lands straight on it (the property list/management
     * page), while a guest is bounced to `/login` by the route's own `auth` middleware and returned
     * to `/my/properties` automatically afterward (Fortify's `LoginResponse` uses
     * `redirect()->intended(...)` — see `FortifyServiceProvider`) — so the "logged in → property
     * list, otherwise → login" behavior comes for free from routing, no conditional here needed.
     */
    public static function buildResidoActionsBlockHtml(): string
    {
        $addPropertyUrl = e(route('my.properties.index', [], false));

        return '<a href="'.$addPropertyUrl.'" class="add-property-link">'
            .'<span class="add-property-icon"><i class="bi bi-plus-lg"></i></span>'
            .e(__('Add Property'))
            .'</a>';
    }

    /**
     * Build the Resido-style menu HTML entirely in PHP (no Blade partial dependency).
     *
     * Accepts the standard $menuTree from Menu::cachedTree() — ['item' => MenuItem, 'children' => [...]]
     * — and returns a fully-rendered <ul> string that can be injected via {!! $residoMenuHtml !!}
     * in the stored database template.
     */
    public static function buildResidoMenuHtml(array $menuTree): string
    {
        $html = '<ul class="ssm-header__menu">';

        if (! empty($menuTree)) {
            foreach ($menuTree as $node) {
                $item = $node['item'];
                $children = $node['children'] ?? [];
                $hasDropdown = ! empty($children);
                $url = e($item->resolvedUrl());
                $label = e($item->label);
                $target = e($item->target);

                $html .= '<li class="ssm-header__menu-item'.($hasDropdown ? ' has-dropdown' : '').'">';
                $html .= '<a href="'.$url.'" class="ssm-header__menu-link"';
                if ($hasDropdown) {
                    $html .= ' role="button" aria-haspopup="true" aria-expanded="false"';
                }
                $html .= ' target="'.$target.'">';
                $html .= $label;
                if ($hasDropdown) {
                    $html .= ' <i class="bi bi-chevron-down ssm-header__caret"></i>';
                }
                $html .= '</a>';

                if ($hasDropdown) {
                    $html .= '<ul class="ssm-header__submenu">';
                    foreach ($children as $child) {
                        $childItem = $child['item'];
                        $childUrl = e($childItem->resolvedUrl());
                        $childLabel = e($childItem->label);
                        $childTarget = e($childItem->target);
                        $html .= '<li><a href="'.$childUrl.'" class="ssm-header__submenu-link" target="'.$childTarget.'">'.$childLabel.'</a></li>';
                    }
                    $html .= '</ul>';
                }

                $html .= '</li>';
            }
        } else {
            // Fallback when no menu items exist in DB
            $homeUrl = e(url('/'));
            $propsUrl = e(url('/properties'));
            $blogUrl = e(url('/blog'));
            $html .= '<li class="ssm-header__menu-item"><a href="'.$homeUrl.'" class="ssm-header__menu-link">Home</a></li>';
            $html .= '<li class="ssm-header__menu-item"><a href="'.$propsUrl.'" class="ssm-header__menu-link">Properties</a></li>';
            $html .= '<li class="ssm-header__menu-item"><a href="'.$blogUrl.'" class="ssm-header__menu-link">Blog</a></li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Build the Resido-style actions HTML (Add Property link) entirely in PHP.
     *
     * No Blade partial needed — all rendered inline and injected via {!! $residoActionsHtml !!}
     * in the stored database template.
     */
    public static function buildResidoActionsHtml(): string
    {
        // Leads to the "My Properties" hub — any logged-in user can browse all active listings and
        // manage their own there; guests are bounced to login (and back) by the auth middleware.
        $addPropertyUrl = e(route('my.properties.index', [], false));
        $addPropertyText = e(__('Add Property'));

        return '<a href="'.$addPropertyUrl.'" class="ssm-header__add-property">'
            .'<span class="ssm-header__add-property-icon"><i class="bi bi-plus-lg"></i></span>'
            .$addPropertyText
            .'</a>';
    }

    /**
     * Render the stored CSS template — same safe-substitution mechanism as renderHtml(), and fixes
     * a real bug: the settings array from getSettingsWithDefaults() is snake_case
     * (`header_bg_color`), but every CSS template variable is camelCase (`{{ $headerBgColor }}`).
     * Passed through Blade::render() directly those never matched (silently rendering as empty —
     * meaning color/font/spacing settings never actually applied), which is fixed here by mapping to
     * the exact camelCase names the templates use.
     */
    public function renderCss(): string
    {
        if (empty($this->css)) {
            return '';
        }

        $s = $this->getSettingsWithDefaults();

        $data = [
            'headerBgColor' => $s['header_bg_color'],
            'headerTextColor' => $s['header_text_color'],
            'headerBorderColor' => $s['header_border_color'],
            'headerHeight' => $s['header_height'],
            'shadowColor' => $s['shadow_color'],
            'borderRadius' => $s['border_radius'],
            'fontFamily' => $s['font_family'],
            'navFontSize' => $s['nav_font_size'],
            'navFontWeight' => $s['nav_font_weight'],
            'navItemPaddingX' => $s['nav_item_padding_x'],
            'navItemPaddingY' => $s['nav_item_padding_y'],
            'logoHeight' => $s['logo_height'],
            'logoWidth' => $s['logo_width'],
            'mobileBreakpoint' => $s['mobile_breakpoint'],
            'navJustify' => match ($s['menu_position']) {
                'left' => 'flex-start',
                'center' => 'center',
                default => 'flex-end',
            },
            'dropdownEnterTransform' => match ($s['dropdown_hover_style']) {
                'slide' => 'translateY(-10px)',
                'scale' => 'scale(0.94)',
                default => 'translateY(-6px) scale(0.97)', // fade (subtle combo, the original default)
            },
        ];

        return $this->safeSubstitute($this->css, $data);
    }

    /**
     * Render the JS template (no variable processing — the JS is a plain, static IIFE with no
     * template placeholders in the current design, so it's returned as-is).
     */
    public function renderJs(): string
    {
        return $this->js ?? '';
    }

    /**
     * The default HTML/CSS/JS shown when creating a brand-new template, and what the seeded demo
     * template starts from. Lives here (not duplicated in Manager::defaultHtml()/defaultCss()/
     * defaultJs() and HeaderTemplateSeeder) specifically so there's exactly one place that defines
     * "what a fresh header template looks like" — the duplication used to let settings quietly
     * drift out of sync between the two (that's exactly how menu_position/dropdown_hover_style
     * ended up wired into the settings form but never actually consumed by the rendered CSS).
     */
    public static function defaultHtmlTemplate(): string
    {
        return <<<'HTML'
<header class="ssm-header {{ $headerClasses }}" id="ssm-header" itemscope itemtype="http://schema.org/WPHeader">
    <div class="ssm-header__inner">
        <div class="ssm-header__brand">
            <a href="{{ $homeUrl }}" class="ssm-header__logo-link" aria-label="{{ $siteName }} — Home">
                {!! $logoHtml !!}
            </a>
        </div>

        <button class="ssm-header__toggle" id="ssm-nav-toggle"
                aria-label="Toggle navigation" aria-expanded="false"
                aria-controls="ssm-main-nav">
            <span class="ssm-header__toggle-line"></span>
            <span class="ssm-header__toggle-line"></span>
            <span class="ssm-header__toggle-line"></span>
        </button>

        <nav class="ssm-header__nav" id="ssm-main-nav" role="navigation" aria-label="Main navigation">
            {!! $menuHtml !!}

            <div class="ssm-header__actions">
                {!! $searchHtml !!}
                {!! $residoActionsHtml !!}
                {!! $ctaHtml !!}
            </div>
        </nav>
    </div>
</header>
HTML;
    }

    public static function defaultCssTemplate(): string
    {
        return <<<'CSS'
/* ── Premium Header Styles ── */
:root {
    --header-bg: {{ $headerBgColor }};
    --header-text: {{ $headerTextColor }};
    --header-border: {{ $headerBorderColor }};
    --header-height: {{ $headerHeight }}px;
    --header-shadow: {{ $shadowColor }};
    --header-radius: {{ $borderRadius }}px;
    --header-font: {{ $fontFamily }};
    --nav-fs: {{ $navFontSize }}px;
    --nav-fw: {{ $navFontWeight }};
    --nav-px: {{ $navItemPaddingX }}px;
    --nav-py: {{ $navItemPaddingY }}px;
    --logo-h: {{ $logoHeight }}px;
    --logo-w: {{ $logoWidth }}px;
    --bp-mobile: {{ $mobileBreakpoint }}px;
    --nav-justify: {{ $navJustify }};
    --dropdown-enter-transform: {{ $dropdownEnterTransform }};
    --ssm-blue: #083F7F;
    --ssm-navy: #052D64;
    --ssm-gold: #FBAB03;
}

.ssm-header {
    position: relative;
    width: 100%;
    height: var(--header-height);
    background: var(--header-bg);
    border-bottom: 1px solid var(--header-border);
    font-family: var(--header-font);
    z-index: 1000;
    transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.ssm-header.is-sticky {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    box-shadow: 0 2px 20px var(--header-shadow);
}

.ssm-header.is-scrolled {
    box-shadow: 0 4px 28px rgba(5, 45, 100, 0.1);
}

.ssm-header.is-transparent {
    background: transparent;
    border-bottom-color: transparent;
    box-shadow: none;
}

.ssm-header.is-transparent.is-scrolled {
    background: var(--header-bg);
    border-bottom-color: var(--header-border);
    box-shadow: 0 4px 28px rgba(5, 45, 100, 0.1);
}

.ssm-header__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.ssm-header__brand { flex-shrink: 0; display: flex; align-items: center; }

.ssm-header__logo-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: opacity 0.2s ease;
}
.ssm-header__logo-link:hover { opacity: 0.85; }

.ssm-header__logo {
    height: var(--logo-h);
    width: auto;
    max-width: var(--logo-w);
    object-fit: contain;
}

.ssm-header__brand-text {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--ssm-navy);
    letter-spacing: -0.02em;
}

.ssm-header__nav {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: var(--nav-justify);
}

.ssm-header__menu {
    display: flex;
    align-items: center;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 2px;
}

.ssm-header__menu-item { position: relative; list-style: none; }

.ssm-header__menu-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: var(--nav-py) var(--nav-px);
    font-size: var(--nav-fs);
    font-weight: var(--nav-fw);
    color: var(--header-text);
    text-decoration: none;
    border-radius: var(--header-radius);
    transition: color 0.2s ease, background 0.2s ease;
    white-space: nowrap;
    position: relative;
}

.ssm-header__menu-link:hover {
    color: var(--ssm-blue);
    background: rgba(8, 63, 127, 0.06);
}

.ssm-header__menu-link.is-active {
    color: var(--ssm-blue);
    font-weight: 600;
    background: rgba(8, 63, 127, 0.06);
}

.ssm-header__menu-link.is-active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 2.5px;
    background: var(--ssm-gold);
    border-radius: 2px;
}

.ssm-header__chevron {
    transition: transform 0.2s ease;
    flex-shrink: 0;
}

.ssm-header__menu-item--has-dropdown:hover .ssm-header__chevron,
.ssm-header__menu-item--has-dropdown.is-open .ssm-header__chevron {
    transform: rotate(180deg);
}

.ssm-header__dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    min-width: 220px;
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(8, 63, 127, 0.08);
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(5, 45, 100, 0.14), 0 2px 8px rgba(5, 45, 100, 0.06);
    padding: 6px;
    list-style: none;
    margin: 0;
    z-index: 1050;
    backdrop-filter: blur(12px);
}

.ssm-dropdown-enter { transition: opacity 0.18s ease-out, transform 0.18s ease-out; }
.ssm-dropdown-enter-start { opacity: 0; transform: var(--dropdown-enter-transform); }
.ssm-dropdown-enter-end { opacity: 1; transform: translateY(0) scale(1); }
.ssm-dropdown-leave { transition: opacity 0.1s ease-in, transform 0.1s ease-in; }
.ssm-dropdown-leave-start { opacity: 1; transform: translateY(0) scale(1); }
.ssm-dropdown-leave-end { opacity: 0; transform: var(--dropdown-enter-transform); }

.ssm-header__dropdown-link {
    display: block;
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.15s ease;
}

.ssm-header__dropdown-link:hover,
.ssm-header__dropdown-link:focus-visible {
    background: rgba(8, 63, 127, 0.06);
    color: var(--ssm-blue);
    outline: none;
    padding-left: 18px;
}

.ssm-header__actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 8px;
    padding-left: 16px;
    border-left: 1px solid var(--header-border);
}

.ssm-header__search { position: relative; display: flex; align-items: center; }

.ssm-header__search-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--header-text);
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}

.ssm-header__search-toggle:hover {
    background: rgba(8, 63, 127, 0.06);
    color: var(--ssm-blue);
}

.ssm-header__search-form {
    position: absolute;
    right: calc(100% + 8px);
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    background: #fff;
    border: 1px solid var(--ssm-blue);
    border-radius: 999px;
    padding: 4px 4px 4px 16px;
    box-shadow: 0 4px 20px rgba(5, 45, 100, 0.12);
    white-space: nowrap;
    z-index: 1051;
}

.ssm-search-enter { transition: opacity 0.2s ease-out, transform 0.2s ease-out; }
.ssm-search-enter-start { opacity: 0; transform: translateY(-50%) scaleX(0.92); transform-origin: right center; }
.ssm-search-enter-end { opacity: 1; transform: translateY(-50%) scaleX(1); transform-origin: right center; }

.ssm-header__search-input {
    border: none;
    background: transparent;
    font-size: 14px;
    color: #1f2937;
    outline: none;
    min-width: 180px;
    padding: 8px 0;
    font-family: inherit;
}
.ssm-header__search-input::placeholder { color: #9ca3af; }

.ssm-header__search-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: var(--ssm-blue);
    color: #fff;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.15s ease;
    flex-shrink: 0;
}
.ssm-header__search-submit:hover { background: var(--ssm-navy); transform: scale(1.05); }

.ssm-header__add-property {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--header-radius);
    text-decoration: none;
    color: #fff;
    background: linear-gradient(135deg, var(--ssm-blue), var(--ssm-navy));
    border: 1px solid var(--ssm-blue);
    box-shadow: 0 2px 8px rgba(8, 63, 127, 0.18);
    white-space: nowrap;
    letter-spacing: 0.01em;
    transition: all 0.2s ease;
}

.ssm-header__add-property:hover,
.ssm-header__add-property:focus-visible {
    background: var(--ssm-navy);
    border-color: var(--ssm-navy);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(8, 63, 127, 0.25);
    outline: none;
}

.ssm-header__add-property-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--ssm-gold);
    color: var(--ssm-navy);
    font-size: 12px;
    flex-shrink: 0;
}

.ssm-header__cta { display: flex; align-items: center; gap: 8px; }

.ssm-header__cta-btn {
    display: inline-flex;
    align-items: center;
    padding: 9px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: var(--header-radius);
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    letter-spacing: 0.01em;
}

.ssm-header__cta-btn--primary {
    background: var(--ssm-blue);
    color: #fff;
    border: 1px solid var(--ssm-blue);
    box-shadow: 0 2px 8px rgba(8, 63, 127, 0.18);
}
.ssm-header__cta-btn--primary:hover {
    background: var(--ssm-navy);
    border-color: var(--ssm-navy);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(8, 63, 127, 0.25);
}

.ssm-header__cta-btn--outline {
    background: transparent;
    color: var(--header-text);
    border: 1px solid var(--header-border);
}
.ssm-header__cta-btn--outline:hover {
    border-color: var(--ssm-blue);
    color: var(--ssm-blue);
    background: rgba(8, 63, 127, 0.04);
}

.ssm-header__toggle {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 40px;
    height: 40px;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 10px;
    border-radius: var(--header-radius);
    transition: background 0.15s ease;
}
.ssm-header__toggle:hover { background: rgba(8, 63, 127, 0.06); }

.ssm-header__toggle-line {
    display: block;
    width: 20px;
    height: 2px;
    background: var(--header-text);
    border-radius: 2px;
    transition: all 0.3s ease;
    transform-origin: center;
}

.ssm-header__toggle.is-active .ssm-header__toggle-line:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.ssm-header__toggle.is-active .ssm-header__toggle-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
.ssm-header__toggle.is-active .ssm-header__toggle-line:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

@media (max-width: {{ $mobileBreakpoint }}px) {
    .ssm-header__toggle { display: flex; }
    .ssm-header__brand { z-index: 1001; }

    .ssm-header__nav {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(16px);
        flex-direction: column;
        justify-content: flex-start;
        align-items: stretch;
        padding: 90px 24px 32px;
        gap: 4px;
        z-index: 999;
        overflow-y: auto;
    }
    .ssm-header__nav.is-open { display: flex; animation: ssm-nav-fadein 0.25s ease-out; }

    @keyframes ssm-nav-fadein {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .ssm-header__menu { flex-direction: column; gap: 2px; width: 100%; }
    .ssm-header__menu-link { padding: 14px 16px; font-size: 16px; justify-content: space-between; width: 100%; }
    .ssm-header__menu-link.is-active::after { display: none; }
    .ssm-header__menu-link.is-active { background: rgba(8, 63, 127, 0.08); }

    .ssm-header__dropdown {
        position: static;
        box-shadow: none;
        border: none;
        border-radius: 0;
        padding: 0 0 4px 16px;
        background: transparent;
        backdrop-filter: none;
    }
    .ssm-header__dropdown-link { padding: 10px 14px; font-size: 15px; }

    .ssm-header__actions {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        margin-left: 0;
        padding-left: 0;
        border-left: none;
        margin-top: 12px;
        padding-top: 16px;
        border-top: 1px solid var(--header-border);
    }

    .ssm-header__search { width: 100%; }
    .ssm-header__search-toggle { display: none; }
    .ssm-header__search-form {
        position: static;
        transform: none;
        box-shadow: none;
        border: 1px solid var(--header-border);
        border-radius: 999px;
        width: 100%;
        padding: 2px 2px 2px 16px;
    }
    .ssm-header__search-input { min-width: 0; flex: 1; width: 100%; }

    .ssm-header__cta { flex-direction: column; gap: 8px; width: 100%; }
    .ssm-header__cta-btn { justify-content: center; width: 100%; padding: 12px 20px; }

    .ssm-header__add-property { justify-content: center; width: 100%; padding: 12px 20px; }
}
CSS;
    }

    public static function defaultJsTemplate(): string
    {
        return <<<'JS'
(function() {
    'use strict';
    var header = document.getElementById('ssm-header');
    var toggle = document.getElementById('ssm-nav-toggle');
    var nav = document.getElementById('ssm-main-nav');
    if (!header) return;

    var isScrolled = false;
    function onScroll() {
        var s = window.scrollY > 20;
        if (s !== isScrolled) { isScrolled = s; header.classList.toggle('is-scrolled', s); }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (toggle && nav) {
        toggle.addEventListener('click', function() {
            var o = nav.classList.toggle('is-open');
            toggle.classList.toggle('is-active', o);
            toggle.setAttribute('aria-expanded', o);
            document.body.style.overflow = o ? 'hidden' : '';
        });
        nav.querySelectorAll('a').forEach(function(l) {
            l.addEventListener('click', function() {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });
        document.addEventListener('click', function(e) {
            if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });
    }

    if ('ontouchstart' in window && nav) {
        nav.querySelectorAll('.ssm-header__menu-item--has-dropdown > .ssm-header__menu-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                var p = this.closest('.ssm-header__menu-item--has-dropdown');
                if (p) {
                    e.preventDefault();
                    p.classList.toggle('is-open');
                    p.closest('.ssm-header__menu').querySelectorAll('.ssm-header__menu-item--has-dropdown.is-open').forEach(function(o) {
                        if (o !== p) o.classList.remove('is-open');
                    });
                }
            });
        });
    }

    nav.querySelectorAll('.ssm-header__menu-item--has-dropdown').forEach(function(item) {
        var t = item.querySelector('.ssm-header__dropdown-toggle');
        var d = item.querySelector('.ssm-header__dropdown');
        if (t && d) {
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    var o = item.classList.toggle('is-open');
                    t.setAttribute('aria-expanded', o);
                }
            });
            d.addEventListener('keydown', function(e) {
                var links = d.querySelectorAll('.ssm-header__dropdown-link');
                var ci = Array.from(links).indexOf(document.activeElement);
                if (e.key === 'ArrowDown') { e.preventDefault(); links[Math.min(ci + 1, links.length - 1)].focus(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); links[Math.max(ci - 1, 0)].focus(); }
                else if (e.key === 'Escape') { e.preventDefault(); item.classList.remove('is-open'); t.setAttribute('aria-expanded', 'false'); t.focus(); }
            });
        }
    });
})();
JS;
    }

    /**
     * Restricted template substitution: only `{{ $identifier }}` (HTML-escaped) and
     * `{!! $identifier !!}` (raw) referencing a bare variable name are recognized — no Blade
     * directives, method calls, or arbitrary expressions are ever evaluated. Anything else in the
     * stored string (including a stray `{{ Something::dangerous() }}`) simply doesn't match the
     * pattern and passes through as inert literal text.
     */
    public static function safeSubstitute(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{!!\s*\$(\w+)\s*!!\}|\{\{\s*\$(\w+)\s*\}\}/',
            function (array $m) use ($data) {
                if ($m[1] !== '') {
                    return (string) ($data[$m[1]] ?? '');
                }

                return e((string) ($data[$m[2]] ?? ''));
            },
            $template
        );
    }

    /**
     * Get the sticky/transparent classes for the header element.
     */
    public function headerClasses(): string
    {
        $settings = $this->getSettingsWithDefaults();
        $classes = [];

        if ($settings['sticky']) {
            $classes[] = 'is-sticky';
        }
        if ($settings['transparent']) {
            $classes[] = 'is-transparent';
        }

        return implode(' ', $classes);
    }
}
