# Module 11: Social Media Management

**Status:** Share buttons still active and unchanged. **The admin-manageable "Social Links" half of this
module was removed on 2026-08-05** — see the second section below. This doc originally covered both as one
feature; they're split into their own sections now that only one survives, so a reader doesn't have to
mentally subtract the removed half from the original text.

**Note:** the doc's "Optional: auto-post to Facebook" line item was intentionally skipped when this module
was first built — explicitly marked optional, not covered by acceptance criteria, and it would require a
Facebook Graph API app + page token that doesn't exist for this dev/demo project.

## Share buttons — still active, unchanged since original build

**"Share this specific page"** — a small reusable partial taking `$shareUrl`/`$shareTitle`, rendering
Facebook/X/WhatsApp/LinkedIn share links built from platform share-URL formats (no SDK/API keys needed —
these are all plain `?u=`/`?text=` GET links).

- **`resources/views/partials/share-buttons.blade.php`** — included on
  `frontend/properties/show.blade.php` (using `url()->current()`/`$property->title`) and
  `frontend/blog/show.blade.php` (`$post->title`).
- **Not admin-configurable and has no database table** — generated per-request from whatever page it's
  included on. This is a **deliberately different, simpler mechanism** than the removed admin-manageable
  Social Links feature below: one was "where do our profiles live" (admin-configured, shown site-wide in
  the footer), this one is "share this specific page" (no admin configuration needed, nothing to manage).
- Uses the `bootstrap-icons` npm package (imported in `resources/css/app.css`) for the platform icons — the
  same self-hosted icon font the (removed) admin Social Links feature also used; removing that feature did
  **not** remove `bootstrap-icons` itself, since Share Buttons and plenty of other UI across the app still
  depend on it.

### Tests

None added for this half either (per the "move fast" instruction spanning Modules 5–12). What was verified:
share button URLs are correctly encoded for titles containing special characters, links open the right
platform share dialog. If you want coverage later: confirm each generated URL's query string for a title
containing `&`/`#`/unicode characters.

## Social Links (admin-manageable footer icons) — REMOVED 2026-08-05

**What this used to be:** a `social_links` DB table + `Admin\SocialLinks\Manager` (`/admin/social-links`)
page where an admin could add Facebook/Twitter/Instagram/etc. rows (platform, URL, optional icon override,
active toggle, drag-drop order), rendered as a row of icon links in the site footer via
`SocialLink::cachedActive()`.

**Why it was removed:** Module 8's Footer Manager (`/admin/footer`) had **already grown its own,
independent inline social-links editor** — a `social_links` JSON array field right on `FooterSetting`,
editable directly on the same page as the rest of the footer, with `footer.blade.php` preferring it over
this module's table and only falling back to `SocialLink::cachedActive()` when the footer's own list was
empty. Two admin surfaces doing overlapping jobs (one of them a network hop and separate sidebar page away
from where the footer itself is edited) was redundant, so the standalone feature was deleted and the
fallback removed — the Footer's own field is now the *only* social-links mechanism, not a preferred one
with a spare.

**Everything deleted:**
- `app/Models/SocialLink.php`
- `app/Livewire/Admin/SocialLinks/Manager.php` + `resources/views/livewire/admin/social-links/manager.blade.php`
- The `/admin/social-links` route and its sidebar link
- `database/seeders/SocialLinkSeeder.php` + `database/factories/SocialLinkFactory.php` — these were still
  registered in `DatabaseSeeder::run()` after the model was deleted in an earlier pass of this same cleanup,
  which meant a fresh `php artisan migrate:fresh --seed` would have thrown `Class "App\Models\SocialLink"
  not found`. Caught while auditing seeders specifically for this reason — **deleting a model means
  grepping `database/seeders/` and `database/factories/` too, not just `app/` and `routes/web.php`.**
- The `socialLinks` variable from `AppServiceProvider`'s footer View Composer, and the corresponding
  `$socialLinks` fallback branch in `footer.blade.php` (`$bottomSocialLinks` is now always
  `collect($footerSetting->social_links ?? [])`, no fallback source).

**Deliberately left alone:** the `social_links` **database table** itself (migration not reverted, no data
dropped) — the code that wrote/read it is gone, but the table is harmless sitting unused. If you want it
gone too, that's a separate, more destructive step (drop the table, which permanently loses any admin
already-entered rows) that wasn't part of this cleanup.

### Gotchas (historical, kept for context — the feature they describe no longer exists)

- **A route referencing a not-yet-created class breaks `php artisan` entirely.** While originally building
  this module, `use App\Livewire\Admin\SocialLinks\Manager as SocialLinkManager;` plus its route were added
  to `routes/web.php` before the class file existed, which made **every** subsequent `php artisan` command
  fail with `UnexpectedValueException: Invalid route action` (artisan bootstraps routes on every
  invocation, even for unrelated commands like `make:migration`). The general lesson still applies to any
  new full-page Livewire component: create the class (even an empty stub) *before* wiring its route.
- `npm install bootstrap-icons` (still installed, still used elsewhere) triggered an `npm audit` report of
  68 vulnerabilities at the time — all confirmed pre-existing transitive vulnerabilities from the Module 9
  CKEditor 5 install, unrelated to `bootstrap-icons` itself. Not auto-fixed (would have forced a breaking
  CKEditor major version upgrade). Still true, still not fixed, still not this module's problem.
