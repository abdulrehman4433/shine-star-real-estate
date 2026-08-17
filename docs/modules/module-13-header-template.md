# Module 13: Header Template System

**Status:** Done (2026-07-29). **Custom Resido-style header** with gradient background, white text, dynamic menu, Add Property button. Fully editable from `/admin/headers` dashboard — no file-based partials required.

## 2026-08-16: mobile toggler icon fix, mobile dropdown dark theme, Add Property button restyle

Three separate small requests against the live "Shine Star Premium" template's stored `css` column (edited
directly via a one-off `php artisan tinker` script doing `str_contains`/`str_replace` on the `HeaderTemplate`
row — see the root README's app-wide gotchas for why this is the standard technique for any stored
Page/HeaderTemplate content edit, not a one-off hack invented here):

- **Mobile hamburger icon was invisible**: `.navbar-toggler-icon` is Bootstrap's own class — its default SVG
  background-image has a dark/black stroke, invisible against `.resido-navbar`'s dark `#002051` background.
  **First attempt got the fix backwards and made it worse**: the white-stroke replacement SVG was applied to
  `.navbar-toggler` (the outer `<button>`) while `.navbar-toggler-icon` (the inner `<span>` that actually has
  Bootstrap's correct `width/height/background-size/position` box model) was set to `background-image: none`
  — so the icon rendered malformed/wrongly-sized instead of just invisible. **Fix: put the white SVG on
  `.navbar-toggler-icon` itself** (with `!important`, since Bootstrap's own compiled CSS also targets that
  exact class and load order isn't guaranteed), leave `.navbar-toggler` alone. **Lesson: when overriding a
  Bootstrap icon-via-background-image class, always check which of the two nested elements (wrapper button vs.
  inner icon span) actually carries the sizing rules — putting the image on the wrong one produces a
  differently-broken result, not just "no change".**
- **Mobile dropdown menu (`.resido-nav .dropdown-menu`, inside the `max-width: 991.98px` media query)**: was
  a light background (`#fafbff`) left over from the desktop dropdown's own light-themed CSS — on mobile the
  whole nav sits inside the dark `.resido-navbar`, so a light dropdown floating on it looked inconsistent
  (and this is exactly the pairing the 2026-08-16 rebrand's contrast audit above was written to catch, just
  found slightly earlier in the same session). Changed to `background-color: #002051` with `.dropdown-item`
  text `#ffffff` and a `:hover` state of `color: #cccccc` — plus a `rgba(255, 255, 255, 0.08)` hover background
  that wasn't explicitly requested but was added because the request only specified hover *text* color, and a
  light-gray hover text color with no background change would have been genuinely illegible against the same
  dark background (`#cccccc` on `#002051` reads fine; `#cccccc` text with no visual hover feedback at all is a
  worse UX than adding a subtle background shift).
- **`.add-property-link`** was a plain text link (blue text, small green circular "+" icon badge, no
  background/padding at all) — restyled into an actual filled button per request: `background: #fdb005`,
  `color: #ffffff`, plus `padding: 10px 20px` + `border-radius: 8px` (not explicitly requested, but a
  background color with zero padding is just a thin colored strip behind the text, not a "button" — the same
  judgment call as the hero-banner-readability fix elsewhere in this doc set) and a `#e29e04` hover shade. The
  small icon badge (`.add-property-icon`, previously green-on-green-tint) was changed to a translucent white
  circle (`rgba(255,255,255,.25)` background, white icon) so it doesn't clash sitting inside the new solid
  amber button.

## 2026-08-14: hover-intent dropdowns (desktop), click untouched (mobile)

The live template's `js` column was still the original dead `ssm-header` IIFE (see the fix note below —
`if (!header) return;` made it a no-op against the current Resido-bootstrap markup). Replaced it wholesale
with a small hover-intent script, since nothing of value was lost:

- **Desktop (`window.innerWidth > 991`):** each `.resido-nav .nav-item.dropdown` opens its `.dropdown-menu`
  on `mouseenter` and closes it on `mouseleave` — but only after a **250ms grace delay**, cancelled if the
  mouse re-enters (either back on the trigger or into the now-open panel) before it fires. This is what
  makes moving the cursor from the trigger link down into the dropdown panel safe: the `margin-top: 10px`
  gap between them would otherwise let a plain `mouseleave` close it mid-transit. Toggles Bootstrap's own
  `.show` class (`display:none` → `block` is already defined by Bootstrap's CSS, not by anything custom
  here) so it stays visually consistent with the framework rather than fighting it.
- **Mobile:** completely untouched — the collapsed nav's dropdown toggle still uses Bootstrap's native
  `data-bs-toggle="dropdown"` click behavior, exactly as before. The new script only calls
  `e.preventDefault()`/`stopPropagation()` on that toggle's click handler when `isDesktop()` is true, so
  mobile taps never reach this new code path at all.
- The mobile hamburger menu itself needs no custom JS either way — it's Bootstrap's own
  `data-bs-toggle="collapse"` against `#residoNavContent`, bundled via `@vite`.

**If you ever add a second header template** (there's currently only the one live "Shine Star Premium" row),
copy this `js` column content into it too if it uses the same `.resido-nav`/`.dropdown` markup shape —
this script is written against those specific classes, not the `ssm-header__` system `defaultJsTemplate()`
produces for brand-new templates (that one already does hover via Alpine `@mouseenter`/`@mouseleave` in
`partials/header-template/menu.blade.php`, a different code path, untouched by this fix).

## 2026-08-14 fix: the live template's HTML/CSS had gone fully static

**Real bug, found while checking that the header actually reflects CMS Menu + Settings.** At some point after
this module was built, the live "Shine Star Premium" row's `html`/`css` columns were replaced (via the
`/admin/headers` editor, or a direct paste of a purchased "Resido" HTML/CSS snippet) with a **different**
Bootstrap-`.navbar`/`.dropdown` design than `defaultHtmlTemplate()` describes above — and that replacement
HTML had **zero** `{{ }}`/`{!! !!}` placeholders in it: the logo was a hardcoded "Resido" text/SVG, all five
nav links were hardcoded `href="#"` items ("Listings"/"Features"/"Pages" with static dropdown items that
didn't correspond to any real menu item), and the "Sign In" button never reflected auth state. None of it
read `Menu::cachedTree('header')` or the `site_name`/`site_logo` settings at all — it looked identical to the
purchased template mockup regardless of what an admin configured under CMS → Menu or Settings. (The template's
stored `js` was *also* stale — a leftover copy of `defaultJsTemplate()` referencing `#ssm-header`/
`#ssm-nav-toggle`/`#ssm-main-nav`, ids that don't exist in this HTML at all — harmless only because its own
`if (!header) return;` guard made it a no-op; the current design doesn't need custom JS since Bootstrap's own
bundled JS already drives `data-bs-toggle="collapse"`/`"dropdown"`.)

**Fix:** added three new inline-PHP builder methods to `HeaderTemplate` — `buildResidoLogoHtml()`,
`buildResidoBootstrapMenuHtml()`, `buildResidoActionsBlockHtml()` — that reproduce the *same* Bootstrap-navbar
markup/classes (`.resido-logo`, `.resido-nav`/`.dropdown-menu`, `.add-property-link`/`.btn-sign-in`) but built
from real data (uploaded logo or site name, `Menu::cachedTree('header')`, and `auth()->check()` for the
sign-in/dashboard button), wired into `renderHtml()`'s `$data` array as `$logoBlockHtml`/`$navMenuHtml`/
`$headerActionsHtml`. The live row's stored `html` was then updated to reference those three placeholders in
place of the hardcoded blocks (`css` got one added rule, `.resido-logo-img`, for when a real logo image is
uploaded instead of the fallback SVG mark). **These are deliberately separate methods from
`buildResidoMenuHtml()`/`buildResidoActionsHtml()` above** (the `ssm-header__`-classed variants used by
`defaultHtmlTemplate()`) rather than edits to those — the two designs use different DOM/class shapes, and nothing
else was using the Bootstrap-navbar shape yet. If you create a **new** header template from scratch (not by
editing this row), it starts from `defaultHtmlTemplate()`/`defaultCssTemplate()`/`defaultJsTemplate()`, which
were already fully dynamic and unaffected by this bug.

**Lesson for editing `/admin/headers` by hand:** pasting a raw HTML/CSS snippet from a purchased theme
directly into the HTML/CSS tabs is fine for getting the *visual* design in place quickly, but any nav/logo/CTA
block copied in verbatim will be static until it's rewired to the `{{ }}`/`{!! !!}` placeholders `renderHtml()`
actually populates (see the table near the top of this doc for the full list) — a visually-correct save is not
the same as a dynamically-correct one, and there's no automated check that catches the difference.

## Architecture

The header template is stored entirely in the database (`header_templates` table):

| Column | Content | Editable from dashboard |
|--------|---------|------------------------|
| `html` | HTML template with `{{ $var }}` / `{!! $var !!}` placeholders | ✅ `/admin/headers` HTML tab |
| `css` | CSS with `{{ $var }}` placeholders for dynamic settings | ✅ `/admin/headers` CSS tab |
| `js` | JavaScript (IIFE pattern, no placeholders) | ✅ `/admin/headers` JS tab |
| `settings` | JSON with colors, sizes, toggles | ✅ `/admin/headers` Settings tab |

### Security: No Blade::render()

The stored HTML/CSS/JS is **never** passed through `Blade::render()` — using `Blade::render()` on user-stored content would be a Remote Code Execution (RCE) vulnerability because `@php ... @endphp` directives would execute arbitrary PHP.

Instead, `HeaderTemplate::safeSubstitute()` does a **single, restricted substitution pass**:
- `{{ $var }}` → HTML-escaped value from the data array (or empty string if missing)
- `{!! $var !!}` → raw (unescaped) value from the data array (for pre-rendered HTML blocks)

No Blade directives, method calls, or arbitrary expressions are ever evaluated.

## Data Flow

```
Admin edits template at /admin/headers
        ↓
HTML, CSS, JS saved to header_templates table
        ↓
Frontend loads → HeaderTemplate::activeTemplate() (cached 1 hour)
        ↓
renderHtml() → builds data array → safeSubstitute() → rendered HTML
renderCss()  → builds data array → safeSubstitute() → rendered CSS
renderJs()   → returns as-is
```

### Variables available in HTML templates

| Variable | Type | Description |
|----------|------|-------------|
| `{{ $siteName }}` | string | Site name from settings |
| `{{ $homeUrl }}` | string | Home page URL |
| `{{ $headerClasses }}` | string | CSS classes like `is-sticky` |
| `{!! $logoHtml !!}` | raw | Logo `<img>` or brand text `<span>` (pre-rendered) |
| `{!! $menuHtml !!}` | raw | Default menu `<ul>` (pre-rendered from Blade partial) |
| `{!! $searchHtml !!}` | raw | Search form HTML (pre-rendered from Blade partial) |
| `{!! $ctaHtml !!}` | raw | Login/Register buttons HTML (pre-rendered from Blade partial) |
| `{!! $residoMenuHtml !!}` | raw | Custom Resido-style menu `<ul>` (inline PHP) |
| `{!! $residoActionsHtml !!}` | raw | Add Property link HTML (inline PHP) |

### Variables available in CSS templates

| Variable | Description |
|----------|-------------|
| `{{ $headerBgColor }}` | Header background color |
| `{{ $headerTextColor }}` | Header text color |
| `{{ $headerBorderColor }}` | Header border color |
| `{{ $headerHeight }}` | Header height in pixels |
| `{{ $shadowColor }}` | Shadow color (rgba) |
| `{{ $borderRadius }}` | Border radius in pixels |
| `{{ $fontFamily }}` | Font family string |
| `{{ $navFontSize }}` | Navigation font size (px) |
| `{{ $navFontWeight }}` | Navigation font weight |
| `{{ $navItemPaddingX }}` | Horizontal padding for nav items (px) |
| `{{ $navItemPaddingY }}` | Vertical padding for nav items (px) |
| `{{ $logoHeight }}` | Logo height (px) |
| `{{ $logoWidth }}` | Logo width (px) |
| `{{ $mobileBreakpoint }}` | Mobile breakpoint (px) |
| `{{ $navJustify }}` | CSS justify-content value based on menu position |
| `{{ $dropdownEnterTransform }}` | CSS transform for dropdown animation |

## Pre-rendered Blocks (No File Dependencies)

The Resido-style custom template uses two pre-rendered blocks generated entirely in PHP (no Blade partials):

### `{!! $residoMenuHtml !!}`

Generated by `HeaderTemplate::buildResidoMenuHtml($menuTree)`:
- Accepts the standard `$menuTree` from `Menu::cachedTree('header')`
- Renders a `<ul class="ssm-header__menu">` with CSS hover dropdowns
- Supports multi-level menus with `has-dropdown` class on parent items
- Outputs child `<ul class="ssm-header__submenu">` when items have children
- Includes `@empty` fallback (Home / Properties / Blog) when no menu items exist

### `{!! $residoActionsHtml !!}`

Generated by `HeaderTemplate::buildResidoActionsHtml()`:
- Renders `<a class="ssm-header__add-property">` with icon and "Add Property" text
- Links to `route('agent.listings.create')`

## Mobile Menu

The mobile menu uses the following CSS classes and JS behavior:

**CSS (in `@media (max-width: 991.98px)`):**
- `.ssm-header__toggle` — displayed as flex, positioned right with `margin-left: auto`
- `.ssm-header__nav` — absolute positioned below header, full width, dark navy background
- `.ssm-header__nav.is-open` — expands with max-height transition
- `.ssm-header__submenu` — static position, matching dark background

**JS (IIFE in database, no DOMContentLoaded nesting):**
- Toggle click → adds/removes `.is-open` class on `#ssm-main-nav`
- Sets `aria-expanded` on toggle button
- Body scroll lock (`overflow: hidden`) when menu open
- Click outside the menu closes it
- Escape key closes the menu
- Plain links (no dropdown) close the menu on click
- All handlers gated on `window.innerWidth > 991` to skip desktop

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/HeaderTemplate.php` | Model, rendering logic, default templates, inline HTML builders |
| `app/Livewire/Admin/Headers/Manager.php` | Admin Livewire component for editing |
| `resources/views/livewire/admin/headers/manager.blade.php` | Admin editor view with CodeMirror tabs |
| `resources/views/frontend/partials/header.blade.php` | Frontend header partial (renders active template) |
| `resources/views/partials/header-template/logo.blade.php` | Logo partial (shared between default and custom templates) |
| `resources/views/partials/header-template/menu.blade.php` | Default menu partial (Alpine.js dropdowns, not used by Resido style) |
| `resources/views/partials/header-template/search.blade.php` | Search partial |
| `resources/views/partials/header-template/cta.blade.php` | CTA buttons partial |
| `app/Providers/AppServiceProvider.php` | View composer for header partial |
| `database/seeders/HeaderTemplateSeeder.php` | Seeds the default Resido-style template |

## Editing from Dashboard

1. Go to `/admin/headers`
2. Edit HTML/CSS/JS in the code editor tabs (with CodeMirror syntax highlighting)
3. Adjust settings (sticky, transparent, colors, sizes, CTA labels, etc.) in the Settings tab
4. Click **Save Draft** or **Save & Publish**
5. The frontend immediately reflects changes (cache cleared on save)

## Default Template

The default template is defined in `HeaderTemplate::defaultHtmlTemplate()`, `defaultCssTemplate()`, and `defaultJsTemplate()`. When a new installation runs `HeaderTemplateSeeder`, it creates a published "Shine Star Premium" template with the Resido-style dark gradient design.

## Gotchas

- **Always use `{!! $var !!}` for pre-rendered HTML blocks** (`$logoHtml`, `$residoMenuHtml`, etc.) and `{{ $var }}` for plain text values (`$siteName`, `$homeUrl`, etc.)
- **Do not use `@foreach`, `@if`, or any Blade directives** in the stored HTML template — `safeSubstitute()` only recognizes `{{ $var }}` and `{!! $var !!}` patterns
- **CSS variables are camelCase** (`{{ $headerBgColor }}`) while settings keys are snake_case (`header_bg_color`) — the mapping is handled in `renderCss()`
- **Logo URLs are relative paths** (`/storage/1/logo.png`) not absolute URLs, to work regardless of `APP_URL` or subdirectory deployment
- **The custom menu and actions HTML are built inline in PHP** (`buildResidoMenuHtml()`, `buildResidoActionsHtml()`) — no Blade partials needed, everything editable from the dashboard
