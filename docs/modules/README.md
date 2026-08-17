# Module Docs Index

One file per module from `property-portal-dev-prompt.md`. Each doc lists what was built, key files,
routes, gotchas, and test coverage for that module only. Read the relevant module file(s) before
making changes instead of re-scanning the whole app.

| # | Module | Status | Doc |
|---|--------|--------|-----|
| 0 | Project Setup & Foundation | Done | [module-00-project-setup.md](module-00-project-setup.md) |
| 1 | Roles & Authentication | Done | [module-01-roles-auth.md](module-01-roles-auth.md) |
| 2 | Property Categories & Types | Done, admin UI later replaced (2026-08-05 — see doc) | [module-02-categories-types.md](module-02-categories-types.md) |
| 3 | Property Listing (Core) | Done + enhanced (2026-08-05: card-grid list, detail page, amenities admin UI replaced) | [module-03-property-listing.md](module-03-property-listing.md) |
| 4 | Property Inquiries & Favorites | Done | [module-04-inquiries-favorites.md](module-04-inquiries-favorites.md) |
| 5 | CRM Module | Done | [module-05-crm.md](module-05-crm.md) |
| 6 | Chat System | Done (upgraded 2026-07-25 four times — see module doc; latest: admin reply input, instant bell sync, bell as modal; 2026-08-16: guest unread badge, conversation close lifecycle + email-based guest resume, widget ring animation, real-time push for guests via a public channel) | [module-06-chat.md](module-06-chat.md) |
| 7 | Dynamic Pages (CMS) + CDN Assets + Reviews | Done + enhanced (2026-07-28 CDN; 2026-08-05 CDN bug fix + Reviews section; 2026-08-14 review re-wire + real seeder; 2026-08-15/16 Hero/How-It-Works/Achievement redesign, external-image cleanup — see doc) | [module-07-cms.md](module-07-cms.md) |
| 8 | Dynamic Menu & Footer Builder | Done + enhanced (2026-07-28) | [module-08-menu-footer.md](module-08-menu-footer.md) |
| 9 | Blog Module | Done + enhanced (2026-08-05: card-grid list, categories/tags admin UI replaced, tags dropdown) | [module-09-blog.md](module-09-blog.md) |
| 10 | SEO Module | Done | [module-10-seo.md](module-10-seo.md) |
| 11 | Social Media Management | Share buttons still active; admin-manageable Social Links **removed 2026-08-05** — see doc | [module-11-social-media.md](module-11-social-media.md) |
| 12 | Notifications & Settings | Done + enhanced (2026-08-17: Backup & Migration + System Requirements Check pages — see doc, and the root `MIGRATION_GUIDE.md`) | [module-12-notifications-settings.md](module-12-notifications-settings.md) |
| 13 | Header Template System | Done + enhanced (2026-07-29 base; 2026-08-14 dynamic-HTML bug fix + hover-intent dropdowns; 2026-08-16 mobile toggler/dropdown/Add-Property-button restyle — see doc) | [module-13-header-template.md](module-13-header-template.md) |
| 14 | Real Estate Projects (societies/blocks/plot sizes/payment plans) | Done + enhanced (2026-08-05 base; 2026-08-14/15/16: bedrooms/bathrooms/sqft/video, real media for all 6 `RealProjectSeeder` projects, several UI reworks — see doc) | [module-14-projects.md](module-14-projects.md) |
| 15 | Contact Messages, Dynamic SMTP, /contact-us | Done (2026-08-17) | [module-15-contact-messages.md](module-15-contact-messages.md) |

**Reference docs (not numbered modules):**
- [brand-preferences.md](brand-preferences.md) — the admin panel + chat widget design system: brand colors,
  the Bootstrap re-theme approach, sidebar/topbar/card/button conventions, chat UI components, and a real
  Alpine/Bootstrap `.modal` bug worth knowing before adding any new Alpine-controlled modal.

## App-wide conventions (apply to every module)

- **Stack:** Laravel 11, Livewire v4 (multi-file class components), Blade, Bootstrap 5, Alpine.js, MySQL.
- **Livewire + Alpine bundling gotcha:** Livewire v4 bundles its own Alpine instance. `resources/js/app.js`
  imports `{ Livewire, Alpine }` from `vendor/livewire/livewire/dist/livewire.esm` (NOT the standalone
  `alpinejs` npm package — it was removed). Plugins (e.g. `@alpinejs/sort`) register via
  `Alpine.plugin(...)` on that same imported instance before `Livewire.start()`.
- **Full-page Livewire components:** our layouts (`admin.layouts.app`, `frontend.layouts.app`) use the
  classic `@yield('content')` / `@section` pattern, so every full-page Livewire component's `render()`
  must end with `->extends('admin.layouts.app')->section('content')` — NOT `->layout(...)`, which expects
  an anonymous-component-style layout with `{{ $slot }}` and will not work here.
  Layouts include `@livewireStyles` + `@livewireScriptConfig` in `<head>` (before `@vite(...)`); there is
  no `@livewireScripts` anywhere (that's handled by our own bundled app.js).
  Routes point directly at the component class, e.g. `Route::get('/admin/categories', CategoryManager::class)`.
  Route model binding into `mount()` works automatically for implicit bindings (e.g. `mount(?Property $property = null)`).
  Layout is `admin.layouts.app` for admin pages, `frontend.layouts.app` for everything else.
- **Livewire component locations:** `app/Livewire/{Admin,Frontend}/...`, views auto-resolve to
  `resources/views/livewire/{admin,frontend}/...` (Livewire's own convention, separate from the plain
  Blade views in `resources/views/{admin,frontend}`).
  Drag-drop reorder pattern (categories, types, amenities): `x-sort` on the container calling
  `$wire.reorder(id, position)`, `x-sort:item="{{ $id }}"` on each row, `x-sort:handle` on the drag handle.
  The Livewire `reorder(itemId, position)` method fetches siblings ordered by `order`, removes the moved
  item, re-splices it at `$position`, and re-saves sequential `order` values.
- **Roles:** `App\Enums\RoleName` (super-admin, admin, agent, agency, user) backs `spatie/laravel-permission`.
  Role middleware alias registered manually in `bootstrap/app.php` (`role` → `Spatie\Permission\Middleware\RoleMiddleware`,
  package doesn't auto-register it for Laravel 11's `bootstrap/app.php` style).
  Route groups: `/admin/*` (super-admin|admin), `/agent/*` (agent|agency), `/account/*` (user), all with
  `['auth','verified','role:...']`.
- **Images:** all image fields use `spatie/laravel-medialibrary` collections, not raw file columns.
  Storage disk is `env('MEDIA_DISK', 'public')` — set `MEDIA_DISK=cloudinary` + `CLOUDINARY_URL` in `.env`
  once real Cloudinary credentials exist (disk already configured in `config/filesystems.php`); local dev
  defaults to the `public` disk (`php artisan storage:link` already run).
- **Slugs:** `spatie/laravel-sluggable`'s `HasSlug` trait on every sluggable model (categories, types,
  amenities, properties). `Property::getRouteKeyName()` returns `'slug'` so route model binding uses slugs.
- **Caching:** category tree / active types cached under keys `property_categories.tree` /
  `property_types.active` via `Cache::remember`, forgotten on any admin write in the respective Livewire
  managers.
- **Seeders:** `database/seeders/DatabaseSeeder.php` calls, in order: `SettingSeeder`, `RoleSeeder`,
  `DemoUserSeeder`, `PropertyTypeSeeder`, `PropertyCategorySeeder`, `PropertyAmenitySeeder`, `PropertySeeder`,
  `LeadSeeder`, `ChatSeeder`, `PageSeeder`, `BlogCategorySeeder`, `BlogTagSeeder`, `BlogPostSeeder`,
  `MenuSeeder`, `HeaderTemplateSeeder`, `PageTemplateSeeder`, `FooterWidgetSeeder`, `CdnAssetSeeder`. Demo
  accounts: `{role}@shinestarmarketing.test` / `password` for super-admin, admin, agent, agency, user.
  **Most of these seeders guard with "skip if data already exists"** — if you add a new seeded row to an
  existing seeder after the table's already been seeded once in your dev DB, that guard will skip it
  silently; add it manually (tinker/SQL) instead of assuming a re-run will pick it up (hit this exact issue
  with Module 9's `MenuSeeder` update).
  **`SocialLinkSeeder`/`SocialLinkFactory` were deleted 2026-08-05** along with the whole `SocialLink`
  feature (module-11) — they were still registered in `DatabaseSeeder::run()` after the model was removed,
  which meant `php artisan migrate:fresh --seed` would have thrown `Class "App\Models\SocialLink" not
  found` on any fresh install. Caught and fixed as part of this same doc/code audit — **if you delete a
  model, always grep `database/seeders` and `database/factories` for it too**, not just `app/` and
  `routes/web.php`.
  **The 10 demo `Project` rows and 5 demo `Review` rows (module-14, module-07) were created ad hoc via
  `php artisan tinker`, not a registered seeder class** — they exist in whatever dev DB they were created
  in, but a fresh `migrate:fresh --seed` will NOT recreate them. If you want them reproducible, that means
  writing a real `ProjectSeeder`/`ReviewSeeder` and registering it — a deliberate follow-up, not done yet.
- **Public catch-all route** (`Route::get('/{slug}', ...)` for CMS pages, Module 7) is the literal last
  line of `routes/web.php` on purpose — any new top-level route must be added above it or it'll be
  shadowed (confusingly 404s instead of an obvious routing error).
- **Tests:** MySQL is the real dev DB (`shine_star_marketing`); `phpunit.xml` forces `DB_CONNECTION=sqlite`
  + `:memory:` for tests so `RefreshDatabase` never touches it. Run with `php artisan test`.
- **Local env:** XAMPP, MySQL running via `C:\xampp\mysql\bin\mysql.exe`, PHP 8.4 with the `exif`
  extension manually enabled in `C:\xampp\php84\php.ini` (was disabled by default, required by medialibrary).
- **`InteractsWithMedia` needs `implements HasMedia` too.** The trait alone compiles fine but throws a
  runtime `TypeError` the moment any media method is called — medialibrary's repository does a strict
  `instanceof HasMedia` check. Always add both to a model that needs file uploads.
- **Livewire `#[On('echo-private:...,{prop},...')]` dynamic placeholders throw if `$prop` is null**
  (`data_get` treats null as "unset"). Any component with a nullable "currently selected X" id that also
  needs a dynamic Echo channel listener must default that property to a non-null sentinel (e.g. `0`), not
  `null` — see Module 6's `ChatBox::$conversationId`.
- **Real-time chat:** Laravel Reverb self-hosted (`php artisan reverb:start` must be running for live
  push — nothing auto-starts it). `broadcast(...)->toOthers()` calls in this app are always wrapped in
  try/catch so a down Reverb server degrades to "no live push" rather than breaking the feature.
- **Header/footer are fully dynamic** (Module 8) via `View::composer('frontend.partials.header'|'footer', ...)`
  in `AppServiceProvider::boot()`, sourcing from `Menu::cachedTree('header')` / `FooterWidget::cachedColumns()`.
  Any test that hits a page rendering the frontend layout now touches the `menus`/`footer_widgets` tables —
  make sure such tests use `RefreshDatabase` (a default Laravel scaffold test forgot this and broke; see
  that module's doc). As of Module 12 the same composers also supply `siteName` and `siteLogoUrl`.
  **`socialLinks` was removed from this list on 2026-08-05** along with the whole admin-manageable Social
  Links feature — see module-11's doc; don't re-add a reference to `SocialLink::cachedActive()` anywhere.
- **This app has TWO frontend document layouts, and both must independently receive any "applies to every
  page" View Composer** — `frontend.layouts.app` (plain Blade-view pages: properties, blog, chat, profile,
  etc.) and `frontend.layouts.page-template` (every CMS `Page` — including the home page whenever a
  published null-slug page exists, which is the common case). A **real bug**, found and fixed 2026-08-05:
  the Module 7/2026-07-28 CDN-assets View Composer was registered only on `frontend.layouts.app`, so
  admin-managed CDN links silently never rendered on the home page or any other CMS page — i.e. most of the
  live site. Fixed by registering `View::composer([...two layout names...], ...)` with an array instead of
  a single string, and by adding the matching `@if (! empty($cdnHeaderAssets) ...)` /
  `$cdnFooterAssets` Blade blocks to `page-template.blade.php` (they'd only ever existed in `app.blade.php`).
  **Any future "site-wide" View Composer must target both layout names**, and any future third frontend
  layout must be added to that array too — there is no single shared parent layout to hook instead.
- **A route referencing a not-yet-created class breaks `php artisan` entirely**, not just that one route —
  artisan bootstraps all routes on every invocation, so `make:migration`, `make:model`, etc. all fail with
  `UnexpectedValueException: Invalid route action` until the class exists. When adding a new full-page
  Livewire component, run `php artisan make:livewire` (or hand-write a stub class + blank view) **before**
  wiring its route into `web.php`, not after (hit this exact issue building the now-removed Module 11 Social
  Links manager).
- **Livewire's `wire:model` on a native `<select multiple>` does not reliably reflect selected options** —
  the server-rendered HTML never includes a `selected` attribute at all (Livewire sets it via client-side JS
  hydration only), and a small `size="3"` listbox looks cramped/broken next to a normal single-line
  `<select>` in the same row. Found on the Blog Post form's Tags field (2026-08-05) and fixed with a
  **Bootstrap-dropdown-styled checkbox panel**: a `<button class="form-select" data-bs-toggle="dropdown"
  data-bs-auto-close="outside">` whose label text is the comma-joined selected names (or a placeholder), and
  a `.dropdown-menu` containing one checkbox per option, each individually `wire:model`-bound to the array
  property (the same reliable per-checkbox binding the Amenities field already used). This looks like a
  normal closed dropdown at rest, opens to a familiar checklist, and both the initial selected state and
  every toggle are 100% server-driven — no reliance on the select-multiple marshalling Livewire does
  client-side. Prefer this pattern over `<select multiple>` for any future array-valued admin field.
- **`HasSeo` trait** (`app/Models/Concerns/HasSeo.php`, Module 10) is applied to `Property`/`Page`/`BlogPost`
  for polymorphic `seo_meta` records — `seo_title`/`seo_description`/etc. accessors fall back from the `seo`
  relation to model-specific defaults. **The `SeoMeta` model needs an explicit `protected $table = 'seo_meta';`**
  — Eloquent's default guess (`seo_metas`) doesn't match the migration's singular table name and throws
  "table not found" at runtime; this bit us once during Module 10/11 smoke testing.
  Any content-visibility-changing action (approve/reject/publish/unpublish/delete on Property, Page, or
  BlogPost) should call `App\Jobs\GenerateSitemap::dispatch()` to keep `public/sitemap.xml` current — there's
  no single shared hook for this, each action method dispatches it explicitly.
- **`Setting` model** (extended in Module 12) now also handles file-type settings (logo/favicon) via
  `spatie/laravel-medialibrary` — remember the `InteractsWithMedia` trait + `implements HasMedia` interface
  both need to be present (same historical gotcha as Module 6's `Message` model). `Setting::getFileUrl($key)`/
  `Setting::setFile($key, $upload)` are cache-backed like `Setting::get()`/`Setting::set()`; always
  `Cache::forget` the corresponding key on write.
- **Currency display:** use `Property::formatted_price` (accessor added Module 12, wraps
  `Setting::currencySymbol()` + `number_format()`) rather than hardcoding a `$` prefix anywhere new — the
  admin-configurable `currency` setting needs to be reflected everywhere a price is shown.
- **Prefer Alpine-only local UI state over Livewire round-trips for anything that should feel instant**
  (open/close toggles, tabs, modals with no server data dependency) — a Livewire `wire:click` method call is
  a real network request even for a one-line boolean flip, and that lag reads as "broken" to users. This was
  a real bug fixed in Module 6's chat widget (2026-07-25): its open/close toggle was `wire:click="toggle"`
  hitting the server just to flip a boolean, which felt unresponsive. Use `x-data`/`x-show`/`@click` instead
  whenever the toggle doesn't need to fetch or persist anything.
- **Nested Livewire components squeezed into a narrow container need their own compact/narrow mode** — don't
  assume a component designed for a full-width page will still be usable at, say, 350px in a floating
  widget/popover. `App\Livewire\Frontend\Chat\ChatBox`'s `$compact` flag (Module 6) is the pattern: render
  either the list or the detail view (never both side by side) below a width threshold, with a back button
  to return to the list.
- **Admin sidebar order (as of 2026-08-05):** Dashboard, **CMS** (collapsible), **Properties** (plain link,
  no dropdown), **Projects** (plain link, no dropdown), **Blog Posts**, **CRM** (collapsible), Blog
  Categories/Tags links (removed — see below), Settings, SEO Settings. This order was deliberately requested
  by the user multiple times over several rounds — don't "tidy" it back to alphabetical or
  group-everything-together without being asked.
- **CRM section in the admin sidebar** is a collapsible parent ("CRM" via Bootstrap
  `data-bs-toggle="collapse"`) containing Leads and Chat — if you add another CRM-adjacent admin page, nest
  it under that same `#crm-submenu` collapse rather than adding a new top-level sidebar item.
- **CMS section in the admin sidebar** is a collapsible parent ("CMS" via Bootstrap
  `data-bs-toggle="collapse"`) containing, in order, **Reviews, Pages, Menus, Header, Footer, CDN**
  (Reviews was added 2026-08-05 and the whole submenu was explicitly reordered — Reviews first, CDN last —
  don't re-alphabetize it). If you add another CMS-adjacent admin page, nest it under this same
  `#cms-submenu` collapse rather than adding a new top-level sidebar item.
- **Properties and Projects are each a single plain sidebar link, not a dropdown** (as of 2026-08-05,
  after several rounds of back-and-forth — see module-02/03/14 docs for the full history). Categories,
  Property Types, and Amenities no longer have sidebar entries or dedicated admin pages at all — see the
  next bullet.
- **Simple admin-editable lookup tables referenced by exactly one parent form should be created via inline
  "quick-add" on that parent form, not a dedicated CRUD/reorder admin page** — this reverses the original
  Module 2 pattern. As of 2026-08-05: `PropertyCategory`/`PropertyType`/`PropertyAmenity` (quick-added from
  `PropertyForm`) and `BlogCategory`/`BlogTag` (quick-added from `Admin\Blog\Posts\Form`) all lost their
  standalone `/admin/categories`, `/admin/types`, `/admin/amenities`, `/admin/blog/categories`, and
  `/admin/blog/tags` pages (Livewire component + view + route + sidebar link, all deleted — and the
  corresponding `CategoryManagerTest`/`TypeManagerTest`/`AmenityManagerTest` PHPUnit files, which then only
  tested dead routes, were deleted too). The pattern: a `public string $newXName = ''` property + a
  `quickAddX()` method that validates, creates the row (root-level / simplest shape only — e.g. quick-added
  categories are always top-level, no parent picker), and immediately selects/checks it in the parent form's
  own field. See `PropertyForm::quickAddCategory()`/`quickAddType()`/`quickAddAmenity()` and
  `Admin\Blog\Posts\Form::quickAddCategory()`/`quickAddTag()` for the exact shape to copy. **Trade-off worth
  knowing:** you can no longer create a *sub*-category through the UI (`PropertyCategory.parent_id` nesting
  is now write-only via tinker/seeders) — if deep category hierarchies come back into scope, that's a
  deliberate feature to reintroduce, not a bug to silently "fix" by guessing at a parent-picker UI.
- **Admin list pages are responsive card grids, not tables** (Properties, Projects, Blog Posts, as of
  2026-08-05) — `<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">`
  (1 column on mobile up to 5 on `xl`, ≥1200px), each card: image/placeholder with an absolutely-positioned
  badge cluster top-left and a favorite/featured toggle top-right, a compact body, and an icon-only button
  footer (secondary actions that don't fit go behind a `bi-three-dots` dropdown rather than a wide button
  group — see the Properties card's Approve/Reject/Expire menu). All three paginate at **20 per page**
  (`->paginate(20)`, was 15). Copy this pattern for any new admin list rather than reaching for a `<table>`.
- **`Illuminate\Pagination\Paginator::useBootstrapFive()` is called in `AppServiceProvider::boot()`**
  (added 2026-08-05) — without it, `{{ $items->links() }}` renders Laravel's default **Tailwind** pagination
  markup even though this project has no Tailwind CSS loaded, so pagination looked unstyled/broken
  everywhere it was used (Blog Posts, Leads, etc. — not just the new card-grid pages). This fixes plain
  Eloquent paginators rendered from a controller-returned Blade view.
  **This was NOT actually the global, one-time fix it was believed to be** — found and fixed 2026-08-14,
  9 days after the note above was written: `Illuminate\Pagination\Paginator::useBootstrapFive()` only
  affects Laravel's own default paginator view. Every Livewire component using the `WithPagination` trait
  (`$this->paginate(...)` inside `render()`) resolves its pagination view through a **completely separate**
  setting, `config('livewire.pagination_theme')`, which was still left at Livewire's own default, `'tailwind'`
  — meaning `{{ $posts->links() }}` inside every such component (at the time: `Frontend\Blog\Listing`,
  `Frontend\Properties\Listing`, `MyListings`, `MyProperties`, `MyFavorites`, `MyLeads`, and the admin
  `Blog\Posts\Manager`, `Chat\Manager`, `Leads\Manager`, `Projects\Manager`, `Properties\Manager`) had been
  silently rendering broken Tailwind markup (`class="flex items-center justify-between"` etc.) the entire
  time, not just the "not just the new card-grid pages" scope the note above assumed. Fixed by changing
  `config/livewire.php`'s `'pagination_theme'` from `'tailwind'` to `'bootstrap'` — Livewire ships a built-in
  `bootstrap` pagination view (`vendor/livewire/livewire/src/Features/SupportPagination/views/bootstrap.blade.php`)
  that renders the exact same `.pagination`/`.page-item`/`.page-link` markup Bootstrap 5 (and this doc's own
  `useBootstrapFive()` note) already assumes everywhere else. **Any future `WithPagination` component is
  covered automatically by this config change — don't add a per-component pagination view override.** If you
  ever add custom CSS targeting `.pagination`/`.page-link` for a specific page, remember Bootstrap's
  responsive pagination markup renders **two** parallel `<ul class="pagination">` blocks — a `d-sm-none`
  one with plain "Previous"/"Next" text buttons for mobile, and a `d-none d-sm-flex` one with numbered
  page pills for larger screens — styling both identically (e.g. forcing every `.page-link` into a fixed-size
  circle) breaks the mobile text buttons; scope circular/pill styling to whichever of the two you actually
  mean.
- **`doctrine/dbal` is now a real dependency** (added for Module 6's guest-chat migration, which needed
  `Schema::table(...)->change()` to nullify a foreign-key column portably across MySQL and the SQLite test
  DB). Raw `DB::statement('ALTER TABLE ... MODIFY ...')` avoids the dependency but only works on MySQL and
  will break the test suite (SQLite has no `MODIFY`) — always use `->change()` for column alterations in
  this project now that the package is installed.
- **NULL-vs-`!=` in SQL:** any column that's nullable (e.g. `messages.sender_id` for guest messages) needs
  `where(fn ($q) => $q->whereNull('col')->orWhere('col', '!=', $value))` instead of a bare
  `where('col', '!=', $value)` — SQL's three-valued logic means `NULL != x` is `NULL`, not `TRUE`, so a plain
  `!=` silently excludes those rows. This produced a real, silent bug in Module 6's unread-message counting.
- **Never name a Livewire component method the same as a key in a wrapping `x-data` scope** (`open`, `show`,
  `close`, etc.) — Livewire evaluates `wire:click="..."` through the same Alpine expression scope as the
  page's own `x-data`, so a same-named Alpine property silently shadows the Livewire method (browser-console
  `TypeError`, no server-side error at all). Caught in Module 6's notification bell.
- **Laravel database notifications** (`php artisan notifications:table`, then `Notification::send()`/
  `$model->notify()`) are the pattern for anything that needs a persistent, bell-icon-visible admin alert —
  `User` already has `Notifiable`. Use the `database` channel alone (not `mail`) when the ask is specifically
  "show me a notification," not "email me." **That said**, Module 6's admin bell ended up querying
  `Conversation` directly instead (simpler, and matched what was actually asked for — "all unread chats," not
  a notification log) — before reaching for database notifications, check whether the underlying model
  already has an "unread" concept you can query straight from, since that avoids a second, easily-out-of-sync
  data source.
- **Don't build a feature (settings fields, buttons, a whole toggle) speculatively "in case it's wanted
  later" once it's confirmed dead** — Module 6's WhatsApp handoff was built, tried, and then explicitly asked
  to be removed; when that happens, remove the settings UI and fields too, not just the visible button —
  an orphaned toggle that does nothing is worse than no toggle.
- **A chat conversation (or any transient contact touchpoint) can be converted into a durable CRM `Lead`
  on demand** — see `Admin\Chat\Manager::moveToLead()` — rather than only auto-creating leads from formal
  inquiries (`PropertyInquiryObserver`). If you add another ad-hoc contact surface, consider whether it
  needs the same "convert to lead, idempotently, via a nullable FK back to the source" treatment.
- **Admin panel brand system (colors, sidebar/topbar, cards, chat UI components, and an Alpine/Bootstrap
  `.modal` gotcha worth knowing before adding any new modal) has its own dedicated doc:**
  [brand-preferences.md](brand-preferences.md). Read it before touching admin CSS, the sidebar/topbar, or
  adding a new Alpine-controlled modal — don't duplicate that detail here.
- **Editing a homepage/header-template block that's stored as a raw HTML/CSS/JS blob in the database** (the
  null-slug `Page` row — see module-07 — and the `HeaderTemplate` row — see module-13) is normally done via a
  small, throwaway `php artisan tinker --execute="include '<scratch-file>.php';"` script: fetch the row, assert
  the *exact* old substring you expect is present (`str_contains`), `str_replace` it, save, and `echo` a
  clear success/failure marker rather than silently doing nothing if the substring didn't match (stored
  content drifts — a prior raw theme re-paste or an admin's manual edit through the dashboard can mean the
  string you expect isn't there anymore, see module-07's and module-13's own "if this ever gets wiped/re-pasted
  again" notes). This is the standard technique for this class of edit, not a one-off hack — there is no admin
  UI for editing an arbitrary substring inside these blobs, and hand-pasting multi-hundred-line HTML/CSS
  through a chat interface is error-prone, so a scratch PHP file + tinker `include` is preferred over inlining
  the whole replacement as a `--execute` one-liner (which also breaks on multi-line heredoc strings in some
  shells). **Always verify the edit rendered** afterward with a live `curl` against the actual page/route, not
  just a "no error" from the tinker script — a `str_replace` that silently matched nothing still reports
  success from PHP's point of view.
- **`Model::addMediaFromUrl($url)->toMediaCollection($name)`** (Spatie Media Library) downloads a remote image
  directly into a model's media collection — the standard pattern anytime real media needs to be fetched from
  an external URL (a developer's own site, a stock-photo host) and attached, no manual `curl`-to-disk step
  needed. **Real, repeatedly-hit gotcha: PHP's default CLI `memory_limit` (128M) is often too small for
  Spatie's GD-based conversion/thumbnail generation** on large source images (multi-MB real photography or
  renders) — throws a fatal "Allowed memory size exhausted" **during the conversion step**, after the base
  media row and original file have already been saved successfully. Net effect: the attach silently
  "half-succeeds" (original file present and correct, `thumb` conversion missing), and if the crash happened
  mid-batch there can also be a stray duplicate/partial media row left behind from the interrupted attempt.
  Always verify after a batch of `addMediaFromUrl` calls: check every attached media's conversion file
  actually exists on disk (`file_exists($media->getPath('thumb'))`), not just that the tinker command printed
  no error. Fix for a failed conversion: re-run under a higher limit (`php -d memory_limit=512M artisan
  tinker ...`, or regenerate just that one file via
  `app(\Spatie\MediaLibrary\Conversions\FileManipulator::class)->createDerivedFiles($media)`) rather than
  deleting and re-adding the media. See module-14's and module-03's 2026-08-16 entries for two real instances
  of this exact failure mode.
- **Never hotlink an externally-hosted image/icon/video** — every image/icon this app displays should be
  either uploaded through Spatie Media Library, bundled via `npm`/Vite (like `bootstrap-icons`), or copied into
  `public/images/` for static non-model-owned assets (a convention established 2026-08-15 for a hero banner
  image — there was no prior place for this class of asset). Several externally-hotlinked stock images/icons
  from the purchased theme's original asset host were found and replaced during 2026-08-15/16 homepage work —
  see module-07's dated entry. If you're changing an image/icon and there's more than one place using the same
  external host, grep the whole `Page`/`HeaderTemplate` HTML+CSS blob for that host before considering the
  change done, not just the one section you were asked about.
