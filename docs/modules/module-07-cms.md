# Module 7: Dynamic Pages (CMS)

**Status:** Done + enhanced (2026-07-28). **Acceptance:** admin builds a page with custom block order, publishes it, renders
correctly on a public URL. ✅ Slug is now optional — empty slug = home page — with duplicate/home-page conflict validation.
Verified via live browser testing (previous smoke tests still pass).

## What was built (original)

- **`pages` table:** `title`/`slug` (unique, `HasSlug`), `status` (draft/published — `App\\Enums\\PageStatus`;
  this one enum covers both the "status" column AND the "show/hide toggle" capability from the spec — they're
  the same thing), `template` (free string, just a cosmetic selector: default/full-width/landing — no
  actual per-template Blade layout was built, it's stored for a future module to use), `meta_title`/
  `meta_description` (simple columns directly on the table, per this module's literal spec — **note:**
  Module 10 (SEO) will introduce a proper polymorphic `seo_meta` table + `HasSeo` trait; when you get there,
  decide whether `Page` migrates to that system or these columns stay as a CMS-specific fallback), `order`
  (admin list ordering, unrelated to Module 8's menu ordering).
- **`page_blocks` table:** `page_id` (cascade), `type` (`App\\Enums\\PageBlockType`: hero/text/gallery/
  contact-form/map/cta), `content` (JSON, cast to `array` — shape varies per type, see
  `PageBlockType::defaultContent()` for what gets pre-filled when a block is added), `order`.
- **Block images use medialibrary, not JSON URLs** — `PageBlock implements HasMedia` with two collections:
  `hero_image` (singleFile, used by the hero block) and `gallery` (multiple, used by the gallery block).
  Text/link fields for every block type live in the `content` JSON column.
- **Blade partials per block type** (shared between the public page AND the admin live preview — this is
  the key architectural point of this module): `resources/views/frontend/pages/blocks/{hero,text,gallery,
  contact-form,map,cta}.blade.php`, each takes a `$block` variable. A tiny shared loader partial,
  `resources/views/frontend/pages/render-blocks.blade.php`, does
  `@include('frontend.pages.blocks.'.$block->type, ['block' => $block])` for each block in order — **this
  exact partial is `@include`d by both the public page view and the admin builder's live-preview pane**,
  so there is only one place that defines what each block type looks like.
- **Admin — `Admin\\Pages\\Manager`** (`/admin/pages`): list, create (creates a blank "Untitled Page" draft
  and redirects straight into the builder — there's no separate "create" modal), publish/unpublish toggle,
  delete, and **drag-drop reorder of pages themselves** (same `x-sort` + `reorder($id, $position)` pattern
  as every other admin manager in this app — see the root README conventions).
- **Admin — `Admin\\Pages\\Builder`** (`/admin/pages/{page:id}/edit` — bound by **id**, not slug, on purpose:
  see gotchas): the actual page builder.
  - Top: page settings form (title, slug, status, template, meta title/description) — separate `saveMeta()`
    action from the blocks below.
  - Left column: block list with drag-drop reorder (`reorderBlocks`), a type dropdown + "+ Add Block"
    button (appends a block with `PageBlockType::defaultContent()` immediately, no modal — you then click
    "Edit" to fill it in), edit/delete per block.
  - Right column: **live preview** — literally `@include('frontend.pages.render-blocks', ...)`, the exact
    same partial the public page uses. Since Livewire re-renders the whole component after every action,
    this preview updates the instant a block is added, edited, reordered, or removed — no separate
    "preview mode" or draft/published-state juggling was needed.
  - Edit-block modal: fields switch per block type (`@switch($editingBlockType)` in the Blade view) — hero
    gets heading/subheading/button+image upload, text gets heading/body, gallery gets a multi-file
    uploader + per-image remove buttons, contact-form gets heading/description only (the form itself is
    fixed, see below), map gets label/lat/lng/zoom, cta gets heading/button.
- **Frontend — `Frontend\\PageController@show(string $slug)`**: looks up the page by slug, 404s if it
  doesn't exist. If not published, only an admin/super-admin can still view it (draft preview) — everyone
  else gets a 404 (not 403 — don't reveal that an unpublished page exists).
- **Contact-form block is functional**, not just decorative: `Frontend\\Pages\\ContactFormBlock` (a small
  Livewire component embedded by the `contact-form.blade.php` block partial) — fixed name/email/message
  fields, emails every admin/super-admin user via `App\\Notifications\\NewContactMessage` on submit.
- **Map block reuses the Leaflet pattern** from Module 3/6 (`wire:ignore` + Alpine `x-data` init).
- **Public catch-all route** — `Route::get('/{slug}', [PageController::class, 'show'])->name('pages.show')`
  is the **very last line** of `routes/web.php`, deliberately.
- **`PageSeeder`:** one published "About Us" page (`/about-us`) with hero → text → cta blocks.

## 2026-07-28 enhancement: optional slug + home page support

### Migration and model changes

- **`pages.slug` made nullable** (`2026_07_28_000000_make_slug_nullable_in_pages_table.php`). The unique
  constraint is kept — MySQL allows multiple NULLs in a unique index, so multiple draft home pages are
  permitted, but only one published home page is enforced at the application level.
- **`HasSlug` trait removed from `Page` model.** Auto-slug generation conflicted with manual slug
  management via the Builder. Slugs are now entirely user-managed. The `getSlugOptions()` method and
  `Spatie\\Sluggable\\HasSlug`/`SlugOptions` imports were removed.

### Builder validation (`Admin\\Pages\\Builder`)

- Slug validation changed from `required|alpha_dash|unique:pages,slug,...` to a custom closure:
  - **`nullable`** — slug is optional
  - **`alpha_dash`** + **`max:255`** — format constraints when provided
  - **Custom closure** with three checks:
    1. **Empty slug (home page):** checks whether another *published* page already has a null slug
       (excluding the current page). If so, fails with: "A published home page already exists. Please
       unpublish it first before setting another page as the home page."
    2. **Non-empty slug:** checks whether another *published* page already uses this slug (excluding the
       current page). If so, fails with: "This URL slug is already in use by a published page. Please
       unpublish that page first before assigning this slug."
    3. **Non-empty slug:** checks whether another *draft* page already uses this slug. If so, fails with:
       "Another page (draft) already uses this slug. Please delete or rename that page first."
- Empty string is converted to `null` before saving via `$resolvedSlug = $this->slug ?: null;`.
- The `$slug` Livewire property type changed from `string` to `?string` (nullable string) to allow null
  assignment from the database.

### Publish confirmation

The "Save & Publish" button in the builder view includes a `wire:confirm` dialog:
- **Page with no slug (becoming home page):** "This page has no slug and will be set as the home page.
  Only one published home page is allowed. Are you sure?" — only fires when the page is NOT already
  published (i.e., it's becoming the home page for the first time).
- **Page with a slug (normal page):** "Publish this page?"
- **Already-published home page being re-saved:** "Publish this page?" (no special warning, since the
  home page is already established).

### HomeController integration (`Frontend\\HomeController`)

The home route `/` (`Frontend\\HomeController@index`) now checks for a published page with null slug
that has custom HTML content. If found, it renders the page's template (HTML/CSS/JS) instead of the
default `frontend.home` view. If no such page exists, the default view is served as before.

### Catch-all route safety (`Frontend\\PageController`)

The catch-all `/{slug}` route's query now includes `whereNotNull('slug')` to exclude home pages
(null slug) — they are handled exclusively by `HomeController`. Without this exclusion, attempting to
access `route('pages.show', $page)` on a page with null slug would throw a "Missing required parameter"
error because the route expects a non-null `{slug}` parameter.

### Manager view improvements

- **Pages with null slug** now display a yellow "Home" badge instead of a bare `//` slug path, making
  it clear which page serves as the home page.
- **View button** uses a conditional: `$page->slug ? route('pages.show', $page) : route('home')` —
  home pages link to `/` instead of the slug-based catch-all route.
- **Slug field in builder** shows placeholder "leave empty for home page" with a form hint explaining
  the behavior.

### Slug field in the builder view

```blade
<div class="form-text">Leave blank to set this page as the home page. Only one published home page is allowed.</div>
```

## Gotchas / things to know

- **Admin edit route binds by `{page:id}`, not the default slug binding.** `Page::getRouteKeyName()`
  returns `'slug'` (needed for the public `pages.show` route and for `route('pages.show', $page)` calls to
  generate clean URLs), but that would make the *admin* edit URL change every time you rename a page's
  slug via the builder. The admin route explicitly overrides with `{page:id}` to bind by primary key.
  **This also means a page with null slug can still be edited** — the admin edit route doesn't need a
  slug at all.
- **`/{slug}` being the literal last route in the file is load-bearing.** Always add new top-level routes
  above this line, never below it.
- **Draft pages 404 for the public, not 403** — deliberate.
- **No policy class for pages/blocks** — consistent with other admin managers.
- **`HasSlug` trait was removed from `Page`** — don't re-add it without considering the impact on the
  Builder's manual slug management. The slug is now entirely controlled by the user through the form.
- **Slug is nullable** — any code querying `Page::where('slug', $value)` should consider adding
  `whereNotNull('slug')` to exclude home pages when not intended. The catch-all route already does this.
- **Only one published page with null slug is allowed** — enforced at the application level in the
  Builder's validation. MySQL's unique index allows multiple NULLs, so draft home pages can coexist.
- **The `View` button in the manager links to `route('home')` for home pages** — if you add a new
  "View" link elsewhere for pages, use the same conditional pattern to avoid the "Missing required
  parameter" error.

## 2026-07-28 addition: CDN Assets manager

A new **CDN Assets** manager was added under the CMS sidebar group (`/admin/cdn`) for managing external
CSS, JavaScript, and font CDN links that load dynamically on the frontend — no hardcoded `<link>` or
`<script>` tags in the layout, everything is stored in the database and rendered from a View Composer.

### Migration and model

- **`cdn_assets` table** (`2026_07_28_020000_create_cdn_assets_table.php`): `location` (header/footer),
  `type` (css/js/font), `url`, `order` (unsigned integer), `is_active` (boolean), timestamps.
- **`App\Models\CdnAsset`** — `$fillable` (`location`, `type`, `url`, `order`, `is_active`), `$casts`
  (`is_active` => boolean), scopes `active()` and `inLocation()`, and a cached static helper
  `cachedForLocation(string $location)` returning active assets for a location ordered by `order`,
  cached for 1 hour under key `cdn_assets.{location}`.

### Admin — `Admin\Cdn\Manager` (`/admin/cdn`, `admin.cdn.index`)

Full-page Livewire component with:
- **Two card sections**: Header Assets and Footer Assets, each with an "+ Add" button
- **Drag-drop reorder** scoped per location (same `x-sort` + splice pattern as every other admin manager)
- **Active toggle** (`bi-eye`/`bi-eye-slash`), edit, delete with confirmation
- **Modal form**: location (header/footer), type (CSS/JS/font), URL, active toggle
- **Cache invalidation** on every write path via `Cache::forget("cdn_assets.{$location}")`

### Route

```php
Route::get('/cdn', CdnManager::class)->name('cdn.index');
```

### Frontend integration (`resources/views/frontend/layouts/app.blade.php`)

A View Composer in `AppServiceProvider::boot()` passes two variables to the layout:

- **`$cdnHeaderAssets`** — rendered in `<head>`:
  - CSS type → `<link rel="stylesheet" href="...">`
  - Font type → `<link rel="stylesheet" href="...">`
  - JS type → `<script defer src="..."></script>`
- **`$cdnFooterAssets`** — rendered before `</body>`:
  - JS type only → `<script src="..."></script>` (CSS and font assets in the footer are ignored since
    they wouldn't render correctly in a `<script>` tag)

### Sidebar

Placed under the **CMS** collapsible parent alongside Pages, Menus, Header, and Footer — see
`brand-preferences.md` for the sidebar submenu pattern.

### Gotchas

- **Only JS assets should be placed in the footer location** — CSS/font assets in the footer are silently
  skipped during rendering to avoid `<script>` tags loading stylesheet URLs.
- **Cache key per location**: each write path forgets `cdn_assets.{location}` — if you add a new write
  path, remember to clear the relevant cache key.

## 2026-08-05 fix: CDN assets weren't actually reaching most of the site

**Real bug, found while auditing "does the CDN feature actually work end-to-end."** The View Composer that
supplies `$cdnHeaderAssets`/`$cdnFooterAssets` was registered only on `frontend.layouts.app`. But the home
page — and every other CMS `Page` — renders through a **different** layout,
`frontend.layouts.page-template` (`show-template.blade.php` → `@extends('frontend.layouts.page-template')`),
which never had the CDN injection Blade blocks at all. Net effect: admin-managed CDN links silently did
nothing on the majority of real traffic (the home page most of all), while still appearing to work on
plain-Blade pages like `/properties` or `/blog`.

**Fix:**
- `AppServiceProvider::boot()`'s View Composer now targets an array,
  `View::composer(['frontend.layouts.app', 'frontend.layouts.page-template'], ...)`, not a single string.
- The same header/footer CDN injection Blade blocks that already existed in `app.blade.php` were copied into
  `page-template.blade.php` (in `<head>` and just before `@stack('scripts')`).
- **Any future third frontend layout must be added to that composer array too** — there's no single shared
  parent layout both extend, so this can't be fixed once and forgotten.

**Also found and fixed in the same pass — a corrupted, conflicting existing row:** one `cdn_assets` row had
a full `<script src="...bootstrap.bundle.min.js"></script>` tag pasted into the `url` column (should have
been a bare URL) and was **active**, duplicating the Bootstrap JS already bundled via `@vite(...)` — two
copies of Bootstrap's JS initializing on the same page double-fires dropdown/modal/tooltip event handlers.
Cleaned the URL and deactivated the row. `Admin\Cdn\Manager::save()`'s validation was tightened
(`'url' => ['required','string','max:500','url','doesnt_start_with:<']`, with a friendly custom message) so
a `<script>`/`<link>` tag pasted into the URL field is now rejected outright instead of silently corrupting
the row.

## 2026-08-05 addition: Reviews ("Good Reviews by Customers" homepage section)

The homepage template (the purchased "Resido"-style HTML/CSS stored on the null-slug `Page` record) had a
**hardcoded, non-editable** testimonials carousel with dummy names/text/stock photos. Made it fully
admin-manageable, following the exact same "DB-driven section swapped into a placeholder" pattern the
Featured Properties section already used:

- **`reviews` table + `Review` model** (`HasMedia`/`InteractsWithMedia`, single-file `photo` collection):
  `customer_name`, `customer_role` (nullable — location/title), `rating` (1–5), `content`, `order`,
  `is_active`. `Review::cachedActive()` mirrors `SocialLink::cachedActive()`'s old shape
  (`Cache::remember('reviews.active', 1 hour, ...)`).
- **`Admin\Reviews\Manager`** (`/admin/reviews`, under the CMS submenu) — the same modal-CRUD +
  drag-drop-reorder + active-toggle shape as every other simple admin manager in this app (see the
  `Admin\Reviews\Manager` reference now used in `brand-preferences.md`'s modal-pattern note, replacing the
  deleted `Admin\SocialLinks\Manager` example).
- **`HomeController::renderReviewsSection()`** — gated by a new `show_reviews_section` setting (toggle lives
  in `Admin\Settings\GeneralManager`, same card as `show_featured_properties`), renders
  `frontend.partials.reviews-section` and passes it as `$reviewsSection` into the `Page::renderHtml()` data
  array — the **stored page HTML's hardcoded carousel block was replaced with a literal
  `{!! $reviewsSection !!}` placeholder** via a one-off data migration (edited the `Page` row's `html` column
  directly, matching where `{!! $featuredPropertiesSection !!}` already sat in the same template).
- **The 5 original dummy reviews were migrated into real `Review` rows** (same names/text) rather than
  deleted outright, so the homepage looks identical until an admin edits them — this was a deliberate choice
  to avoid a jarring "reviews just disappeared" moment on first deploy of this change.
- **Real bug hit while building this:** the very first test render cached an **empty** `reviews.active`
  result (ran before any `Review` rows existed), and `Cache::remember` doesn't re-run once a key exists —
  the section stayed empty even after seeding rows, until `Cache::forget('reviews.active')` was called
  manually. **Any time you seed data behind a `Cache::remember`-backed accessor, clear that cache key
  afterward** — don't assume the first successful write will also refresh a cache that was already primed
  empty.

## 2026-08-14: re-wired after a raw theme re-paste, real `ReviewSeeder` added, long-review CSS fix

The home `Page` row's `html`/`css` had been wiped by an unrelated mistake in this same working session (an
over-broad column removal, since corrected — see module-13's doc) and the user re-pasted a **fresh, unmodified**
copy of the purchased "Resido" theme's HTML/CSS to recover the rest of the homepage. That fresh copy naturally
came with the theme's own **hardcoded** reviews carousel (lorem-ipsum text, "Adam Williams"/"Retha Deowalim"/etc.)
instead of the `{!! $reviewsSection !!}` placeholder this module originally swapped in — the customization
doesn't survive a raw re-paste of the source file, only the literal `Page` row content did (and that was gone).

- **Re-applied the same swap**: found the theme's hardcoded `<!-- REVIEWS -->` block in the Home page's `html`
  column (a `#reviewCarousel` Bootstrap carousel with two hand-written slides of fake names/quotes) and replaced
  it with `{!! $reviewsSection !!}` again, same as the original 2026-08-05 addition described above.
- **`database/seeders/ReviewSeeder.php` now actually exists** (registered in `DatabaseSeeder`, after
  `CdnAssetSeeder`) — closing the gap this doc's parent bullet flagged ("the 5 original dummy reviews... were
  never a registered seeder"). Ten reviews, deliberately varied in length (one-liners to ~90-word) and role
  (homeowner/tenant/investor/agent/first-time buyer), so a fresh `migrate:fresh --seed` reproduces a realistic,
  non-trivial reviews section without relying on tinker again. Guarded by `if (Review::query()->exists()) return;`
  like every other seeder in this app.
- **Long-review layout fix**: `.review-card p` (in the Home page's own `css` column, not a project-wide
  stylesheet — this styling lives per-page, same as the rest of the purchased theme's CSS) now clamps to 6
  lines via `-webkit-line-clamp` instead of letting a long review stretch the card — and every other card in
  the same row, since Bootstrap's grid stretches columns to equal height — to match it. `.review-card` became a
  flex column with `.review-user` pinned via `margin-top:auto`, so the reviewer photo/name/role always sits at
  the same spot regardless of how many lines the (now-clamped) review text takes. Also added a `768px` mobile
  tweak (`.section-pad`/`.review-card` padding) alongside the theme's existing single mobile breakpoint.
- **If the Home page's `html`/`css` ever gets wiped or re-pasted again**: the fix is exactly this — find the
  hardcoded reviews carousel block and swap it for `{!! $reviewsSection !!}`, and re-check whether
  `{!! $featuredPropertiesSection !!}`/`{!! $projectsSection !!}` (the other two `HomeController`-supplied
  placeholders) also need re-swapping, since a raw theme re-paste reverts all three at once, not just Reviews.

## 2026-08-15/16: Hero, "How It Works", and "Achievement" sections redesigned from user-supplied references

Same technique as the Reviews section and every other homepage tweak in this doc: find the relevant block in
the null-slug `Page` row's stored `html`/`css` columns and edit it directly (a scratch `php artisan tinker`
script doing `str_contains`/`str_replace` on the `Page` model, not through the admin Pages Builder UI — the
blocks being targeted are inside the purchased theme's raw HTML, not `page_blocks` rows, so there's no builder
UI for them at all).

- **Hero background image**: swapped from an external hotlinked stock photo (`resido-v2.smartdemowp.com/...`,
  with a dark gradient overlay + white text) to two different **local**, user-supplied images in turn (both
  copied into a new `public/images/` directory — this app had no prior convention for static, non-model-owned
  theme images; everything else goes through Spatie Media Library's `storage/app/public/{id}/` per-model
  folders, which doesn't apply here since a hero banner isn't owned by any Eloquent model). Each image swap
  needed different `background-position`/`background-size` handling since the two images have different
  compositions — a full-bleed city-skyline silhouette (`background-size: cover`, bottom-anchored) vs. a
  right-aligned isometric illustration with a mostly-transparent left half (`background-size: contain`,
  `right bottom`, so the whole illustration always fits without being cropped or scaled disproportionately at
  any viewport width — a fixed-height sizing approach was tried first and rejected for occasionally scaling
  the image wider than intended). **Both supplied images were light/pale**, which broke the existing dark
  overlay + white text design — the overlay was changed to a light gradient and text color from white to
  dark navy/gray, a change the image swap request didn't explicitly ask for but was a necessary consequence
  (white text on a near-white image is unreadable, same judgment call as several other places in this
  project). Also added `background-blend-mode: color-burn` and changed `min-height` twice (700px, then 520px)
  per explicit follow-up requests — `background-size: contain` means the illustration auto-scales with
  `min-height` changes, no separate resize needed each time.
- **"How It Works?" section**: redesigned from a user screenshot — removed the small "Process" eyebrow tag
  (not present in the reference), replaced the old centered cards (plain number-in-a-circle) with left-aligned
  cards each having a light-blue quarter-circle decorative corner, a large numeral, a Bootstrap Icon (`bi-geo-alt-fill`/
  `bi-people-fill`/`bi-clipboard-check-fill`), heading, and description — plus curved SVG arrow connectors
  between cards (desktop only, `d-none d-md-flex`, hidden when cards stack on mobile).
- **"Achievement" section**: same treatment — removed the redundant "Achievement" eyebrow tag + separate "Our
  Achievement Stats" subheading (now just one "Achievement" heading, matching the reference), and replaced 4
  externally-hotlinked icon images (same `resido-v2.smartdemowp.com` external domain as the old hero photo)
  with bundled Bootstrap Icons (`bi-building`/`bi-house-check`/`bi-tag`/`bi-people`). Icon color went through
  two rounds: green (`#22c55e`, matching a reference screenshot) then blue (`#3d64e4`, a later explicit
  color-swap request) — both via a single `--green` CSS custom property the icons reference, even after the
  color stopped being green (renaming the variable wasn't requested, just its value).
- **Found and removed one more external-image dependency while checking "no more `smartdemowp` references"**
  after fixing Achievement's icons: the bottom `.cta-agent` "Become an Agent" CTA banner still had a hotlinked
  background photo from the same external domain. No replacement photo was supplied for that specific section,
  so it was replaced with a plain gradient background rather than fabricating or downloading an unlicensed
  third-party photo to fill the gap — **don't assume a "replace icon/image" request is fully done until you've
  grepped the whole `Page.html`/`Page.css` blob for the same external domain**, since these purchased-theme
  pages often hotlink the same stock-asset host in more than one unrelated section.
- **This is the same "no externally-hosted asset" requirement module-14's real-project media work (2026-08-16)
  was built around** — the two aren't directly related features, but both sessions converged on "download and
  self-host, don't hotlink" as the standing rule for any image/icon/video this app displays.

## Tests

None added for any of the 2026-07-28/2026-08-05 enhancements (explicitly skipped, consistent with every
other "move fast" module). What was verified: HTTP smoke tests for public page rendering, admin role
restriction, draft-vs-published visibility, and catch-all route shadowing (original module); the CDN
composer fix and Reviews section were verified via direct `curl`/`Livewire::test()` checks against the live
dev homepage (confirmed active CDN assets render, the corrupted Bootstrap-JS row no longer appears, and all
5 seeded reviews render in the carousel) rather than committed test files.
