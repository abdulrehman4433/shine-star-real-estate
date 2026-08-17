# Module 10: SEO

**Status:** Done. **Acceptance:** every page/property/post has editable meta; sitemap.xml valid and
auto-updates; schema visible in page source. ✅ Verified via HTTP smoke tests (property/blog detail pages
render `<script type="application/ld+json">` with correct `@type`, `robots.txt` returns 200 with the seeded
directives, `php artisan app:generate-sitemap` produces a valid `sitemap.xml` with all approved
properties/published pages/published posts) + full existing test suite (75/75). **No new automated tests
were written** — per the "move fast" instruction from Module 5 onward; user is testing this manually.

## What was built

- **`seo_meta` table** (polymorphic): `model_type`/`model_id` (via `$table->morphs('model')`), `meta_title`,
  `meta_description`, `meta_keywords`, `og_image`, `canonical_url` (all nullable strings), `schema_json`
  (nullable json), unique on `(model_type, model_id)`. **Table name is `seo_meta` (singular)** — the `SeoMeta`
  model must declare `protected $table = 'seo_meta';` explicitly, since Eloquent's default guess is
  `seo_metas` (see Gotchas).
- **`HasSeo` trait** (`app/Models/Concerns/HasSeo.php`) applied to `Property`, `Page`, `BlogPost`:
  - `seo(): MorphOne` relation to `SeoMeta`.
  - `seo_title`/`seo_description`/`seo_keywords`/`seo_image`/`seo_canonical`/`seo_schema` accessors — each
    prefers the `seo` relation's value, falling back to a model-appropriate default (excerpt/description/
    content trimmed to 160 chars, `featured_image_url`, `url()->current()`).
  - `defaultSeoSchema()` — generic `WebPage` JSON-LD fallback; overridden per model (see below).
  - `updateSeo(array $attributes)` — `updateOrCreate` helper used by every save path.
- **Per-model schema overrides:**
  - `Property::defaultSeoSchema()` → `RealEstateListing` (address, geo, `offers.priceCurrency` from
    `Setting::currencySymbol()`/`Setting::get('currency')`).
  - `BlogPost::defaultSeoSchema()` → `Article` (headline, author, datePublished, dateModified).
  - `Page` **does not** duplicate title/description fields in its own UI — it already had `meta_title`/
    `meta_description` columns from Module 7. `Page::getSeoTitleAttribute()`/`getSeoDescriptionAttribute()`
    check the `seo` relation first, then fall back to those existing native columns, then `title`. Only
    `meta_keywords`/`canonical_url` were added as new fields to the Pages Builder UI.
- **Admin SEO fields:**
  - `resources/views/admin/partials/seo-fields.blade.php` — shared partial (title/description/keywords/
    canonical) included by `PropertyForm` and Blog `Posts\Form`, the two models with no native meta columns.
  - Pages Builder gets a bespoke two-field addition (keywords/canonical) directly in its existing "Page
    Settings" card instead of the shared partial.
- **Global SEO settings** (`Admin\Settings\SeoManager`, `/admin/settings/seo`): default meta title/
  description, `robots.txt` editor (raw textarea persisted to the `seo_robots_txt` setting), GA Measurement
  ID, Google Search Console verification string, and a manual "Regenerate Sitemap" button.
- **`robots.txt`** — a plain route (`/robots.txt`, placed **before** the Module 7 catch-all `/{slug}`) reading
  the `seo_robots_txt` setting and returning `text/plain`.
- **Sitemap** (`spatie/laravel-sitemap`): `App\Jobs\GenerateSitemap` (queueable) builds home/properties/blog
  static URLs plus every `Property::approved()`, `Page::published()`, `BlogPost::published()` record, and
  writes `public/sitemap.xml`. Dispatched (`GenerateSitemap::dispatch()`) from every path that changes what's
  publicly visible: `PropertyForm::save()`, `Admin\Properties\Manager::approve()/confirmReject()/expireNow()`,
  `Pages\Builder::saveMeta()`, `Pages\Manager::toggleStatus()/delete()`, Blog `Posts\Form::save()`, Blog
  `Posts\Manager::toggleStatus()/delete()`. A console command (`php artisan app:generate-sitemap`, via
  `GenerateSitemapCommand`) runs it synchronously for manual/cron regeneration.
- **Schema.org output partial** (`resources/views/partials/seo-meta.blade.php`) — `@push('meta')` block
  rendering description/keywords meta tags, canonical link, OG tags, and the JSON-LD `<script>` (via
  `array_filter` to drop null schema keys). Included on `properties/show`, `pages/show`, `blog/show`.
- **`tracking-head.blade.php`** — GSC verification meta tag + GA `gtag.js`, both conditional on the
  corresponding setting being non-empty.

## Gotchas / things to know

- **`SeoMeta::$table` must be set explicitly to `'seo_meta'`.** This was a real bug caught during this
  module's own smoke testing (not just a hypothetical): the migration creates `seo_meta` (singular, matching
  the doc's literal table name), but Eloquent's default table-name guess for a model called `SeoMeta` is the
  pluralized snake case, `seo_metas`. Without the explicit `$table` property every `$model->seo` access threw
  `SQLSTATE[42S02]: Base table or view not found`. If you rename this model or copy the pattern for another
  polymorphic table, always double check the guessed table name matches what the migration actually created.
- **`GenerateSitemap::dispatch()` calls are scattered across many action methods** on purpose — there's no
  single "on save" hook shared by Property/Page/BlogPost, so every status-changing or content-changing action
  needs its own explicit dispatch call. If you add a new action that changes public visibility (e.g. a bulk
  approve action), remember to dispatch the job there too.
- `robots.txt` route must stay **above** the catch-all `/{slug}` page route in `routes/web.php`, same
  ordering constraint as `/blog` and other top-level routes from prior modules.

## Tests

None added this module (per the same "move fast" instruction as Modules 5–9). Verified via HTTP smoke tests
described above plus the full existing 75-test suite (no regressions). If you want coverage later, natural
cases: `HasSeo` accessor fallback chain (seo relation → model fallback → generic default) for each of the
three models, `Page`'s native-column fallback specifically (it's the one model with a 3-level chain instead
of 2), `GenerateSitemap` job output structure (right URLs included/excluded by status), and the
`robots.txt` route reading the live setting value.
