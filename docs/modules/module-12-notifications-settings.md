# Module 12: Notifications & Settings

**Status:** Done. **Acceptance:** key events trigger emails; settings changes reflect site-wide. ✅ Verified
via HTTP smoke tests (General/SEO admin settings pages all load for an authenticated super-admin; site
name/logo/currency settings feed into the header/footer/property price displays via cache-backed `Setting`
reads) + full existing test suite (75/75). **No new automated tests were written** — per the "move fast"
instruction from Module 5 onward; user is testing this manually.

**Note:** the doc's "Optional: SMS notifications" line is explicitly marked in the spec as *"skip — no
reliable free tier; stub UI only"* — unlike other optional items in prior modules (which were fully skipped),
this one explicitly asks for a non-functional stub, so a disabled-by-default toggle with a "Coming soon"
badge and explanatory text was built, but it sends nothing.

**2026-08-05:** the "Homepage Sections" card in `Admin\Settings\GeneralManager` (`/admin/settings`) gained a
second toggle, `show_reviews_section`, alongside the existing `show_featured_properties` — same shape
(`Setting::set('show_reviews_section', ...)`, checked by `HomeController::renderReviewsSection()`). See
module-07's Reviews section for the full story. The original mention of a "Social Links admin settings
page" above has been removed from this doc's verification claim — that page never lived under Settings to
begin with (it was its own top-level admin page), and it was deleted entirely on 2026-08-05 anyway; see
module-11.

## What was built

- **Email notifications for key events** — most were already implemented in earlier modules; this module
  fills the one gap the spec calls out:
  - `PropertyApproved` / `PropertyRejected` (**new this module**) — sent from
    `Admin\Properties\Manager::approve()`/`confirmReject()` to `$property->owner`. `PropertyRejected` include
    the `rejection_reason` and links back to the agent's edit page; `PropertyApproved` links to the live
    listing.
  - `NewPropertyInquiry` (Module 4), `LeadAssigned` (Module 5), `TaskDueReminder` (Module 5),
    `NewContactMessage` (Module 7) — pre-existing, unchanged.
- **Global settings** (`Admin\Settings\GeneralManager`, `/admin/settings`):
  - Site name, logo upload, favicon upload (via `Setting::setFile()` — medialibrary single-file collection
    added to the `Setting` model itself: `implements HasMedia`, `use InteractsWithMedia`,
    `registerMediaCollections()` registers a `'file'` singleFile collection; `Setting::getFileUrl($key)`/
    `Setting::setFile($key, $upload)` are the read/write helpers, both cache-backed like `Setting::get()`).
  - Contact email/phone/address.
  - Currency (`Setting::CURRENCIES` const — USD/EUR/GBP/PKR/INR/AED/SAR symbol map) and timezone (full
    `DateTimeZone::listIdentifiers()` list) selects.
  - Maintenance mode toggle — calls `Artisan::call('down', ['--secret' => '...'])`/`Artisan::call('up')`
    directly from the Livewire component, gated behind a `wire:confirm` since it's a site-wide, immediately-
    visible action.
  - SMS notifications stub toggle (see note above) — persists `sms_notifications_enabled` but nothing reads
    it to actually send anything.
- **Site name/logo now used site-wide**, not just in the `<title>` tag from Module 10:
  `AppServiceProvider`'s header View Composer gained `siteName`/`siteLogoUrl`; the footer composer gained
  `siteName` too. `frontend/partials/header.blade.php`'s brand link now shows the uploaded logo `<img>` if
  set, else the site name text; the footer's copyright line uses `$siteName` instead of the hardcoded
  `config('app.name')`.
- **Currency setting now applied to actual price displays**, not just the Module 10 schema.org
  `offers.priceCurrency` value (which already read the setting). Added `Property::getFormattedPriceAttribute()`
  (uses `Setting::currencySymbol()` + `number_format()`) and replaced every hardcoded `${{ number_format($property->price, 0) }}`
  across property show, admin properties manager, my-favorites, my-listings, and the property card partial
  with `{{ $property->formatted_price }}`.

## Gotchas / things to know

- **`Setting::CURRENCIES` is a symbol *map*, not a currency-code passthrough** — `formatted_price` prints the
  mapped symbol (e.g. `$`, `£`, `Rs `) followed directly by the number, no space for symbol currencies, a
  trailing space baked into the map value for code-style ones (PKR/AED/SAR). If you add a new currency to the
  admin dropdown, add its symbol to `Setting::CURRENCIES` too, or it'll fall back to printing the raw code
  plus a space.
- **Maintenance mode toggle actually takes the whole site down** (`php artisan down` under the hood) — it's
  wired to a real `wire:confirm` for a reason. Don't click "Take Site Down" in the admin UI unless you mean
  it; recovering requires hitting the `--secret` bypass URL or running `php artisan up` from the CLI.
- **`Setting` needing `InteractsWithMedia` + `HasMedia` together** is the same historical gotcha from Module
  6 (`Message` model) — both the trait AND the interface are required, or medialibrary's internal
  `instanceof HasMedia` check throws a `TypeError` at runtime. `Setting` was written with both from the
  start this time, but it's worth re-flagging since it's an easy thing to drop when copy-pasting a model.
- The SMS toggle is genuinely inert — if a future module wants real SMS, it needs a provider integration
  (Twilio, etc.) and something that actually reads `sms_notifications_enabled` before sending; right now
  nothing checks that setting anywhere.

## 2026-08-17 addition: Backup & Migration + System Requirements Check

Two new pages under Settings, explicitly requested as "make sure everything's the same if the project
shifts to a new system" — a full migration needs the project files (copy/re-deploy), a database export,
and the uploaded-files folder (the database only stores *paths* to uploads, never the file bytes). See
`MIGRATION_GUIDE.md` in the project root for the full walkthrough these two pages are built around.

- **`Admin\Settings\BackupManager`** (`/admin/settings/backup`) — two on-demand downloads:
  - **Database backup**: `App\Services\DatabaseBackupService::dumpToFile()` generates a plain `.sql` file
    (schema + every row, every table) in **pure PHP** — deliberately not a wrapper around the `mysqldump`
    binary (unlike most "Laravel backup" packages), since a new server might not have that binary
    installed or on `PATH` at all (managed hosting, a different OS, a stripped container), and that would
    silently defeat the entire point of "works on a new system." The only real requirement is a working
    PDO MySQL connection, which the app needs to run at all anyway. Streams row-by-row via `chunkById()`
    directly to disk (never builds the whole dump in memory) so this doesn't degrade as the database
    grows. Verified end-to-end: generated a dump of the real dev database, imported it into a throwaway
    database via the plain `mysql` CLI, and confirmed table count (51) and spot-checked row counts
    (projects: 11, properties: 16, media: 90) matched exactly.
  - **Media files backup**: `App\Services\MediaBackupService::zipToFile()` zips `storage/app/public`
    (every Spatie Media Library upload — property/project photos, brochures, review photos, logo, blog
    images, chat attachments) with paths stored relative to that folder, so extracting the zip on the new
    server just means unpacking it into the same `storage/app/public` location — no path rewriting needed.
    Verified: zipped the real dev `storage/app/public` (188 files, ~137MB, mostly this session's fetched
    project/property photos) and confirmed the zip's internal paths and file count.
  - Both are generated into `storage/app/backups` (private, not web-accessible) and returned via
    `response()->download(...)->deleteFileAfterSend(true)` — nothing is left sitting on disk after the
    browser finishes downloading it.
- **`Admin\Settings\SystemCheckManager`** (`/admin/settings/system-check`) — a live checklist meant to be
  run on a brand-new server right after copying the project files, before importing anything: PHP version
  (`>= 8.2`, matching `composer.json`), 16 required PHP extensions **with the reason each is needed**
  (composer.json declares zero `ext-*` requirements explicitly, so this list — cross-referenced against
  `php -m` on this working dev system — is the actual source of truth; most notably `exif`, which this
  app's own README already flags as "disabled by default, required by medialibrary, easy to forget"),
  Composer dependencies present, `.env`/`APP_KEY`, database connectivity, `storage/`/`bootstrap/cache/`
  writability, the `public/storage` symlink, whether frontend assets are built
  (`public/build/manifest.json`), pending migrations, and Reverb credential configuration. Every failing
  row shows a one-line fix, not just a red "Fail" badge — the point is a new-server setup issue gets caught
  with an actionable answer instead of a confusing 500 error three steps later.
- **Both pages are plain sidebar links directly below "SEO Settings"**, matching the existing flat
  Settings-area pattern (no new collapsible submenu introduced) — same `role:super-admin|admin` middleware
  group as every other admin route, verified unauthenticated requests to both new routes 302-redirect same
  as any other admin page.

## 2026-09-24 addition: four deployment-focused System Check rows + cPanel deploy guide

Added after a real "works locally, assets broken after uploading `public/build` to cPanel" report —
each new row catches one of the four ways that class of failure actually happens, all verified by
`php -l` + the existing suite rather than a live cPanel deploy:

- **APP_URL matches this site** — compares `config('app.url')` with `request()->root()`. A
  copy-pasted `http://localhost:8000` in the server's `.env` is invisible everywhere except in
  uploaded-image URLs (the `public` disk's `url` in `config/filesystems.php`), i.e. "site loads but
  every photo is a broken link". Host mismatch → **fail**; scheme/port-only mismatch (behind SSL
  termination) → **warning**, since that's frequently a false alarm.
- **Compiled asset files present** — parses `public/build/manifest.json` and confirms every `file`/
  `css`/`assets` entry it references exists on disk. Catches a *partially* uploaded build (manifest
  made it, assets didn't) and the Windows-zip-extracted-as-`build/build/…` nesting, both of which
  present as asset 404s → HTML from `index.php` → dead module scripts. Returns **no row at all** when
  the manifest itself is missing, because `viteBuildCheck()` already fails on that (deliberate: one
  red row per problem, not two).
- **No dev-server asset pointer (`public/hot`)** — a file written by `npm run dev` that makes Laravel
  point every page's CSS/JS at `localhost:5173` if it's deployed. Warning (not fail) because its
  presence is legitimate during local development.
- **Debug mode** — warns when `APP_DEBUG=true` outside `local`, since error pages then print `.env`
  values to visitors.

**Matching build-side fix in `vite.config.js`:** `npm run build` now deletes `public/hot` before
building (`remove-hot-file` plugin, `apply: 'build'` only — dev server untouched), so the deployed
artifact can't carry a dev-server pointer even if someone forgets.

**`public/.htaccess` also gained a guarded `mod_mime` block** (`.js/.mjs/.css/.woff/.woff2` → correct
`Content-Type`) — some shared hosts ship a reduced MIME map, and a browser refuses to execute
`<script type="module">` served with the wrong type, which reads as "JS does nothing" with a
console MIME-type error. Wrapped in `<IfModule mod_mime.c>` so a host without mod_mime can't 500.

Docs: new **`docs/DEPLOYMENT.md`** (plain step-by-step for this project's actual cPanel layout —
`public_html/` holds the public files, app in `public_html/laravel/` — including the pre-zip commands,
zip exclusions, the 3-line `index.php` edit, and ordered asset/media fix checks) plus
`docs/DEPLOYMENT_SHARED_HOSTING.md` rewritten as a short extras-only list (cron, SSL, email,
permissions, Reverb/cloudinary fallbacks), cross-linked from `MIGRATION_GUIDE.md` and the README. `.env.production` was corrected to actual production values
(`APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`, `LOG_LEVEL=error`,
`QUEUE_CONNECTION=sync`, and `VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"` instead of the literal
`YOUR_KEY` that any build from that file would have hardcoded into the deployed JS bundle — Vite
loads `.env.production` over `.env` for mode `production` and expands `${VAR}` itself).
- **Separately verified `php artisan migrate:fresh --seed` succeeds cleanly end-to-end** against a
  throwaway database (`shine_star_marketing_migration_test`, created and dropped for this check only — the
  real dev database with its accumulated real content was never touched): all 58 migrations and all 23
  registered seeders ran with zero errors. This confirms a genuinely from-scratch install works, which is
  the complementary case to the backup/restore path above (that path is for migrating the *live* site with
  its real content; a bare `migrate:fresh --seed` is for spinning up a fresh dev environment and won't
  reproduce data that was added via one-off `tinker` scripts rather than a seeder — see the migration
  guide's "things that are easy to forget" section, and this app's own module docs for which content that
  currently applies to).

## 2026-08-17 fix: media/database backup hit PHP's memory_limit for real users

Reported directly: "Allowed memory size of 134217728 bytes exhausted" (128M — PHP's default) when clicking
the media backup download. **This is exactly the same failure mode as the `addMediaFromUrl`/GD-conversion
memory gotcha already documented in module-14/module-03** — 128M is tight for any real file-processing
work — but this time it shipped without being caught, for a specific, worth-remembering reason: **it was
verified via `php artisan tinker`, and tinker in this environment happened to have the same 128M limit as
the web server, but with none of the request overhead already spent.** A bare CLI script has the whole
128M to itself; a real Livewire admin request has already spent part of that budget on Blade rendering,
Livewire's own hydration/snapshot machinery, and session handling before either backup service's first line
even runs — so the exact same code path succeeded in the verification and failed for a real user hitting
the actual admin page. **Lesson: verifying a memory/resource-heavy Livewire action via tinker calling the
service class directly isn't equivalent to exercising it through the component method** — if a real
regression check matters, call the component method itself (`Livewire::test(...)->call('methodName')`), and
consider explicitly forcing a matching baseline (`php -d memory_limit=128M artisan tinker ...`) rather than
trusting the environment's default to already match production.

- **Fix**: `BackupManager::raiseResourceLimitsForBackup()` calls `ini_set('memory_limit', '1024M')` and
  `set_time_limit(300)` at the top of both `downloadDatabase()` and `downloadMedia()`, before either backup
  service runs. This has to happen *before* the call, not wrapped in a try/catch around it — PHP's "Allowed
  memory size exhausted" fatal is not a catchable `Throwable`, so no amount of exception handling around
  the dump/zip calls could have recovered from it after the fact; raising the ceiling ahead of time is the
  only fix that actually works. `ini_set`/`set_time_limit` are safe to call unconditionally — on hosts that
  disable them (rare, some restrictive shared hosting), they silently no-op rather than throwing.
- **Also added a `memory_limit` check to the System Requirements Check page** (warns below 256M,
  recommends 512M+) — informational, since the Backup page now self-raises its own limit regardless, but
  a low baseline affects other memory-heavy operations in this app too (large image uploads/conversions).
- **Re-verified correctly this time**: ran `php -d memory_limit=128M artisan tinker` (forcing the exact
  same starting limit a production web request would have) and called
  `Livewire::test(BackupManager::class)->call('downloadMedia')` directly — confirmed it completes without
  a fatal error and that `ini_get('memory_limit')` reads back `1024M` after the call, for both the media zip
  and the database dump.

## Tests

None added this module (per the same "move fast" instruction as Modules 5–11). Verified via HTTP smoke tests
described above plus the full existing 75-test suite (no regressions). If you want coverage later, natural
cases: `PropertyApproved`/`PropertyRejected` notifications actually being sent on the corresponding admin
actions (same shape as the existing `InquiryFormTest`'s "the owner and admins are notified" test),
`Setting::getFileUrl()`/`setFile()` round-tripping a fake uploaded file, `formatted_price` for each currency
in the map, and the maintenance-mode toggle's `Artisan::call()` side effect.
