# Module 14: Real Estate Projects (societies, blocks, plot sizes, payment plans)

**Status:** Done (2026-08-05). **Acceptance:** admin manages real-estate development projects (housing
societies like "Bahria Town Phase 8") separately from individual `Property` listings — each project has a
location on a map, is divided into blocks/precincts, and each block offers area sizes (5/7/8/10 Marla,
1 Kanal, etc.) with a full payment plan (booking amount, installments, possession charge). List, add/edit,
and read-only detail pages, all admin-only — **no public-facing frontend page was built for this module**,
only the admin side (explicitly scoped that way; see "What was deliberately not built" below).

## Why this is a separate feature from `Property`

A `Property` (Module 3) is one individual listing — a specific house/plot/shop, owned by one agent, with one
price. A `Project` is a whole **development/society** that doesn't belong to any single agent, isn't "for
sale" as one unit, and is structured very differently: it has named sub-areas (blocks), and within each
block, several *sizes* of plot are on offer, each with its own price and installment schedule. Trying to
force this into the existing `Property` model (e.g. one `Property` row per size per block) would have lost
the block/society grouping and made the payment-plan fields (`booking_amount`, `installment_count`, etc.)
nonsensical on a model that's also used for a finished house with bedrooms/bathrooms. New tables instead.

## Schema

- **`projects`** — `title`, `slug` (unique, `HasSlug`), `society_name`, `developer_name` (nullable),
  `type` (`residential`/`commercial`/`mixed_use`, `App\Enums\ProjectType`), `development_status`
  (`upcoming`/`under_construction`/`completed`/`delivered`, `App\Enums\ProjectDevelopmentStatus`),
  `description`, `address`/`city`, `lat`/`lng` (decimal 10,7 — same shape as `Property.lat/lng`),
  `contact_phone`/`contact_email`, `is_featured`, `is_active`, `order`.
- **`project_blocks`** — `project_id` (cascade), `name`, `description` (nullable), `order`.
- **`project_plot_sizes`** — `project_id` (cascade), `project_block_id` (**nullable**, `nullOnDelete` — a
  plot size can exist with no specific block, shown as "No Specific Block" on the detail page), `size_value`
  (decimal 8,2) + `unit` (`marla`/`kanal`, `ProjectPlotSize::UNITS` const map), `category` (free string,
  e.g. "Residential Plot"), then the **payment plan fields, inlined onto the same row rather than a separate
  table**: `total_price`, `booking_amount`, `confirmation_amount`, `installment_amount`,
  `installment_count`, `installment_frequency` (`monthly`/`quarterly`/`half_yearly`/`yearly`,
  `ProjectPlotSize::INSTALLMENT_FREQUENCIES` const map), `possession_amount`, `notes`, `order`.
  **Deliberate simplification:** a real-world payment plan is 1:1 with a specific plot size (5 Marla and
  10 Marla always have different prices/plans), so there was no need for a third `project_payment_plans`
  table — that would have added a level of nesting to the admin form for no real benefit.
- **`project_amenity_project`** — pivot reusing the **existing** `property_amenities` table/model (Module 3)
  rather than creating a parallel `ProjectAmenity` — a swimming pool is a swimming pool whether it's on a
  `Property` or a `Project`, no reason to duplicate the lookup table or its (removed, Module 2/3) admin UI.

## Models

- **`App\Models\Project`** — `HasSlug`, `HasSeo` (Module 10 — polymorphic SEO fields, same as
  `Property`/`Page`/`BlogPost`), `HasMedia`/`InteractsWithMedia` (`cover` singleFile, `gallery` multiple,
  `brochure` singleFile PDF, `thumb` conversion 480×320 same as `Property`), `blocks()`/`plotSizes()`
  (`hasMany`, both `orderBy('order')`), `amenities()` (`belongsToMany` via `PropertyAmenity`),
  `scopeActive()`/`scopeFeatured()`, `typeEnum()`/`developmentStatusEnum()` accessor methods (same pattern
  as `Property::statusEnum()`), `defaultSeoSchema()` → `ResidentialComplex` JSON-LD.
- **`App\Models\ProjectBlock`** — plain, `belongsTo(Project)`, `hasMany(ProjectPlotSize)`.
- **`App\Models\ProjectPlotSize`** — plain, `belongsTo(Project)`, `belongsTo(ProjectBlock, 'project_block_id')`
  as `block()`, `getLabelAttribute()` (e.g. `"5 Marla"`, trims trailing zeros off `size_value`).

## Admin components

- **`Admin\Projects\Manager`** (`/admin/projects`, list) — search (title/society), type filter, responsive
  **card grid** (same shape as Properties/Blog Posts, `row-cols-1` → `row-cols-xl-5`, `->paginate(20)`),
  featured-star toggle, visible/hidden toggle (**icon is `bi-toggle-on`/`bi-toggle-off`, deliberately not
  `bi-eye`/`bi-eye-slash`** — the card also has a separate "View" button using an eye icon, and the two
  looked confusingly identical when both were eye icons; this was a direct user-reported fix), delete
  (cascades to blocks/plot sizes via the FK).
- **`Admin\Projects\Form`** (`/admin/projects/create`, `/admin/projects/{project:id}/edit`) — one large form:
  basic info, location (click-to-drop-pin Leaflet map, identical `wire:ignore`+Alpine pattern to
  `PropertyForm`), **Blocks** (simple repeatable name/description rows, same "delete+recreate on save"
  pattern as `PropertyForm`'s `customFeatures`), **Area Options & Payment Plans** (repeatable rows, each a
  bordered mini-card with size/unit/block-select/category + all the payment-plan fields), amenities
  (checkboxes, reusing `PropertyAmenity`), media (cover/gallery/brochure), contact info, visibility toggles,
  SEO fields (shared `admin.partials.seo-fields` partial).
- **`Admin\Projects\Show`** (`/admin/projects/{project:id}`, `admin.projects.show`) — read-only detail page,
  reached via "View" on the list. Groups plot sizes **by block** (a table per block, plus a "No Specific
  Block" table for unassigned ones) rather than one flat table, since which block an area option belongs to
  is the whole point of the grouping.

### The block-index ↔ block-id remapping trick (the one genuinely tricky part of this module)

Blocks and plot sizes are both **repeatable array rows** on the same form, and a plot size needs to say
"which block am I in" — but while the form is open, a newly-added block has no database ID yet, and even an
*existing* block's ID shouldn't leak into the plot-size row's array shape (it needs to survive the block
being reordered/removed while the form is still open, before either side is saved). The fix:

- Each plot-size row stores `block_index` — the **position of its block inside the `$blocks` array**
  (`''` means "no specific block"), never a real `project_block_id`, while the form is open.
- On `mount()` (editing an existing project), a `$blockIndexById` map is built from the loaded `$blocks`
  collection's *positions*, and each plot size's real `project_block_id` is translated back into a
  `block_index` string for the dropdown to pre-select correctly.
- `removeBlockRow($index)` walks every plot-size row and fixes up `block_index`: exact match → reset to `''`
  (that block is gone); anything with a higher index → decrement by one (everything after the removed block
  shifted down). **This must happen in the Livewire method, not just visually** — if you only removed the
  block from `$blocks` without also walking `$plotSizes`, a plot size that pointed at index 2 would now
  silently point at whatever block happens to occupy index 2 after the shift, not "no block."
- On `save()`, blocks are created **first** (empty-name rows silently skipped, matching every other
  repeater in this app), building a fresh `$blockIdByIndex = [index => real new id]` map, and *then* plot
  sizes are created using that map to resolve each row's `block_index` into a real `project_block_id` (or
  `null`). Blocks and plot sizes are both deleted-and-fully-recreated on every save (same pattern as
  `PropertyForm::customFeatures`), which is why the ID mapping has to happen fresh every single save, not
  just once.

Verified via a `Livewire::test()` scratch script (not a committed test — see Tests below): create a project
with 2 blocks + 2 plot sizes (one per block), remove block 0, confirm the plot size that pointed at block 0
now shows "no block" and the plot size that pointed at block 1 still correctly resolves to the *same* block
(not a different one) after the index shift.

## Routes & sidebar

```php
Route::get('/projects', ProjectManager::class)->name('projects.index');
Route::get('/projects/create', ProjectForm::class)->name('projects.create');
Route::get('/projects/{project:id}/edit', ProjectForm::class)->name('projects.edit');
Route::get('/projects/{project:id}', ProjectShow::class)->name('projects.show');
```
All under the existing `/admin` prefix/middleware group. **Bound by `{project:id}`, not the default slug
binding** — same reasoning as Module 7's Pages Builder and Module 9's Blog Post Form: editing a project's
title regenerates its slug, which shouldn't also change the admin edit URL.

**Sidebar placement went through several rounds of explicit user changes, in order** (if you're reading git
history / old screenshots and the position looks different from what's live now, this is why):
1. First built: **Properties** became a collapsible submenu containing Properties/Categories/Property
   Types/Amenities/**Projects** all together.
2. Then: Projects pulled out to its own top-level link, positioned after the Properties submenu and before
   CRM.
3. Then: Categories/Property Types/Amenities removed from the submenu entirely (see module-02/03's inline
   quick-add change) and the whole **Properties dropdown collapsed into a single plain link** — Projects'
   own top-level link removed too, on the theory it'd be reachable via a cross-nav tab bar on the Properties
   list page instead.
4. **Final (current) state:** Projects reinstated as its own top-level plain link, positioned directly below
   Properties and above CRM — and the cross-nav tab bar from step 3 was removed from both list pages since
   it became redundant once Projects had its own sidebar entry again.

Current final order: Dashboard, CMS, **Properties**, **Projects**, Blog Posts, CRM, Settings, SEO Settings.

## Demo data

10 sample projects (Bahria Town Phase 8, DHA Phase 9, Gulberg Corporate Centre, Al-Kabir Town Phase 3,
Emporium Mall Extension, Faisal Town Phase 2, Blue World City, Park View City, Lake City, Capital Smart
City) — spread across all three `type` values and all four `development_status` values, each with 2 blocks
and 3 priced plot-size options (5 Marla / 8 Marla / 1 Kanal with full payment plans). **Created via
`php artisan tinker`, not a registered seeder class** — see the root `README.md`'s Seeders bullet. If you
want this reproducible via `migrate:fresh --seed`, write a real `ProjectSeeder` from this same data.

## What was deliberately not built (at the time — since superseded, see below)

**No public-facing frontend page.** The user's request was explicitly "there a list page and add/edit page"
— admin-only. A real estate site would presumably want a public `/projects` listing and a project detail
page (with the map, blocks, and payment plans shown to visitors), but that's a separate, not-yet-requested
piece of work; don't assume it exists when working on frontend code.

**Update (2026-08-14): this is no longer true — a public frontend now exists.** `Frontend\ProjectsController`
(`projects.index` → `/projects`, paginated 9/page, `Project::active()`; `projects.show` → `/projects/{project}`,
404s for inactive unless the viewer is admin/super-admin) plus `resources/views/frontend/projects/{index,show}.blade.php`
were added at some point after this doc was written (exact session not tracked in this doc — found already live
while wiring the homepage's "Featured Projects" section, see below). **This doc's "no public page" claim is
stale — verify against `php artisan route:list | grep project` before trusting it.**

- **Home page integration**: `HomeController::renderProjectsSection()` (queries `Project::active()->featured()->take(6)`)
  and `resources/views/frontend/partials/projects-section.blade.php` also already existed, unused — the Home
  `Page` row's stored `html` just wasn't pointing at the `{!! $projectsSection !!}` placeholder (the section
  the doc originally described, "Recent Property For Rent", was the theme's own hardcoded properties scroll-row).
  Swapped that hardcoded block for the placeholder so the homepage's project carousel is finally live. The
  partial deliberately **reuses the exact same `id="rentRow"`/`id="rentPrev"`/`id="rentNext"` ids** the
  properties version used — the Home page's own `js` column has a small scroll-button script targeting those
  same ids, so removing the old hardcoded section and dropping the placeholder in its place didn't need any JS
  changes at all, it just now controls the projects row instead.
- **`database/seeders/ProjectSeeder.php` now exists** (registered in `DatabaseSeeder`, after `ReviewSeeder`) —
  closing the exact gap this doc's "Demo data" section above flags ("created via tinker, not a registered
  seeder class"). 10 projects, all `is_active`/`is_featured` true (so the full set is reachable from both the
  home carousel, capped at 6 by `take(6)`, and the public `/projects` index, uncapped), each with 2 blocks and
  3 priced plot-size options — same shape as the original tinker-created demo data, just reproducible now.

## 2026-08-14: public detail page (`projects.show`) redesigned from a reference file

The public `frontend/projects/show.blade.php` was previously a plain, unstyled Bootstrap page — functional
(gallery carousel, blocks/plot-size tables, amenities, Leaflet map, sidebar contact card) but visually bare.
Rebuilt against a reference HTML/CSS file the user supplied (a generic "property detail" template — soft
white "panel" cards, an accordion body, a gradient-header contact card, pill badges), adapted to what a
`Project` actually has instead of copying it verbatim:

- **Kept from the reference, restyled to fit real data**: the two-photo gallery-with-arrows header (now
  cycling through `cover` + all `gallery` images via a small Alpine `x-data` — no images degrades to a "No
  Image" placeholder, exactly one image hides the nav arrows and second photo slot), the panel/accordion
  layout for Detail & Features / Description / Amenities / Location, and the gradient contact card.
- **Added, not in the reference**: an "Area Options & Payment Plans" accordion section (the blocks/plot-size
  tables that already existed on the old page — a real Project feature the generic template had no concept
  of), a Brochure download accordion item (only rendered `@if ($project->brochure_url)`), and a "More
  Projects" sidebar list (new `$otherProjects` query in `ProjectsController::show()`, 4 other active projects).
- **Deliberately dropped from the reference** (no real feature/data to back them — would have been fake,
  non-functional UI): Property Video, Rating, Write a Review, and Reviews accordion sections (Projects have
  no review/rating system); the generic mortgage "Calculate" sidebar widget (replaced by the real payment-plan
  tables instead, which is the actually-relevant equivalent for Pakistani real estate installment plans); the
  Save/Compare sidebar buttons (no favorites/comparison feature exists for Projects — kept the real Share
  button, dropped the two fake ones); the "Agent" framing on the contact card (Projects have no assigned
  agent — shows `developer_name`/`society_name` and `contact_phone`/`contact_email` instead, with an honest
  "No contact details have been added" fallback rather than a form with nowhere real to submit to, since no
  `ProjectInquiry` system exists — unlike `PropertyInquiry` for Module 3/4).
- All new CSS is scoped under a single `.project-detail` wrapper class (custom properties like `--pd-navy`
  are also scoped there, not `:root`) specifically so it can't leak into the rest of the frontend — this page
  doesn't share a design system with the homepage's Resido theme or the admin's `ssm-` brand system, it's a
  third, self-contained style block, same pattern this app already uses per-page for the Header Template and
  Page Builder's stored CSS.

## 2026-08-14: `RealProjectSeeder` — real (non-fabricated) data for 6 named developments

The user asked for 6 specific real Pakistani housing developments to be added, "get all common data from
their website" — fetched each developer's own site (WebFetch; `faisaltownphase-2.com` 403'd, so that one
came from a web search of aggregator sites instead) and wrote a **new, separate, idempotent** seeder rather
than editing `ProjectSeeder` or doing this via one-off tinker commands (the anti-pattern this doc's own
"Demo data" section already flags for the original 10).

- **Two titles collided with existing `ProjectSeeder` placeholder rows** — "Blue World City" and "Faisal
  Town Phase 2" already existed with generic, made-up descriptions. `RealProjectSeeder` uses
  `Project::updateOrCreate(['title' => ...], [...])`, so re-running it corrects those two rows **in place**
  (same id/slug) with the real developer/location/description instead of creating duplicates. The other
  four ("Kingdom Valley", "Lakeshore City", "Seventeen Villas", "ESMR Heights - Faisal Hills") are new.
  Blocks/plot sizes are deleted and recreated on every run for all six, same pattern
  `Admin\Projects\Form::save()` already uses.
- **"ESMR Heights - Faisal Hills" is a genuinely separate project, not a duplicate/typo of Faisal Hills** —
  it's Etimaad Skylite Mall and Residencia, a commercial/residential high-rise built by a *different*
  developer (Etimaad International / Skylite Builders) physically located inside the larger Faisal Hills
  society. Modeled as its own `Project` row (`society_name` set to "Faisal Hills" for the location context,
  `developer_name` correctly attributed to Etimaad/Skylite, not Faisal Hills' own developer).
- **Nothing was invented where the source didn't publish it** — several fields are deliberately null or
  use only the site's own real block/size names with no pricing, rather than guessing a plausible number:
  Lakeshore City's developer name (the site never states it), Blue World City's and Kingdom Valley's
  block-level pricing (no plot prices were published on either site), and 5 of Faisal Town Phase 2's 6
  Overseas Enclave plot sizes' pricing (sizes were published, prices weren't). **Seventeen Villas is the
  one project with real, complete pricing** — its three "series" (Executive/Prime/Royal) each had a
  published starting price, modeled as one block + one priced `ProjectPlotSize` per series.
- If you add more real (non-demo) projects later, follow this seeder's shape — `updateOrCreate` keyed on
  title, delete-then-recreate blocks/plot sizes, and leave a field null with an inline comment explaining
  why rather than filling it with a plausible-looking guess.

## 2026-08-14: `development_status` removed entirely (form, display, column) — confirmed destructive, by request

Explicitly asked for, with the destructive scope confirmed in advance (a column drop discarding all 16
projects' stored status values was one of two options presented; the user chose it knowingly). Removed:

- The `development_status` column (migration; `down()` restores it as a nullable-default string, but the
  actual values are gone for good, not recoverable).
- `App\Enums\ProjectDevelopmentStatus` — deleted entirely, nothing else referenced it once the model
  accessor was gone.
- `Project::developmentStatusEnum()` and `development_status` from `$fillable`.
- The "Development Status" `<select>` in `Admin\Projects\Form` (the two remaining fields in that row,
  Developer and Project Type, widened from `col-md-4` to `col-md-6` to fill the space).
- The status badge everywhere it rendered: admin Manager card grid, admin Show detail header, public
  `/projects` index cards, public `/projects/{slug}` header badge and its "Detail & Features" accordion row,
  and the homepage's "Featured Projects" carousel.
- The `development_status` value from every seeder (`ProjectSeeder`, `RealProjectSeeder`).
- **The homepage carousel's and the detail page's "no starting price" fallback** used to show the
  development status label in that slot (e.g. "Under Construction") when a project had no priced plot
  sizes to compute a starting price from — replaced with a plain "Contact for pricing" string in both
  places, since there's no longer a status value to fall back to.
- **Same day, a related but separate cleanup**: the public detail page's header card had a bottom row
  showing live `{{ $project->blocks->count() }} Blocks` / `{{ $project->plotSizes->count() }} Area Options`
  / `{{ $project->society_name }}` — for projects with zero real blocks/plot sizes (e.g. Kingdom Valley,
  which has neither, see the `RealProjectSeeder` entry above), this rendered as a visibly broken-looking
  "0 Blocks · 0 Area Options" line, plus the society name duplicated the page's own `<h1>` title for
  several projects. Removed that whole row (and its now-orphaned `<hr>` divider) from the header card —
  the same Blocks/Area Options counts are still shown further down in the "Detail & Features" accordion,
  so no information was actually lost, just the redundant/broken-looking duplicate at the top.
- **Also, while in the same form**: the Cover Image and Gallery Images fields were already functionally
  optional (`'nullable|image|max:4096'` — this was never a bug), just not *labeled* as optional the way
  "Developer (optional)" and "Brochure (PDF, optional)" already were on the same form. Added the same
  "(optional)" suffix to both labels for consistency — no validation/behavior change, label text only.

## 2026-08-14/15/16: heterogeneous product types (bedrooms/bathrooms/sqft), video, and a round of UI cleanup

Prompted by re-analyzing the 6 real developments in `RealProjectSeeder`: they don't all sell the same product.
Seventeen Villas sells built villas (bedroom/bathroom counts matter), ESMR Heights sells high-rise shops/
apartments (conventionally priced per **sqft**, not marla/kanal), while the other four are still raw-land
plot sellers. All of the following is purely additive — no existing data was dropped.

- **Schema**: `project_plot_sizes` gained nullable `bedrooms`/`bathrooms` (unsigned tinyint), and
  `ProjectPlotSize::UNITS` gained a `'sqft' => 'Sqft'` option alongside marla/kanal.
  `ProjectPlotSize::getBedBathLabelAttribute()` renders `"2 Bed · 3 Bath"` (or just `"2 Bed"`, or `null` for a
  plain plot with neither set).
  `projects` gained a nullable `video_url` string column + `Project::getVideoEmbedUrlAttribute()`, which
  converts a plain YouTube/Vimeo *watch* URL (the normal "share" format) into its embeddable `iframe` src via
  regex — so the admin form takes a normal pasted link, not a pre-built embed URL.
- **`RealProjectSeeder` updated**: Seventeen Villas' three villa series now carry bedroom/bathroom counts
  (illustrative — real per-series bedroom counts were never published, only sizes/prices were, see that
  seeder's existing "nothing invented where the source didn't publish it" doc comment); ESMR Heights' shops/
  apartments converted from marla to sqft, with bedroom/bathroom counts added to the apartment entries.
- **UI churn, in the order it happened** (each was its own explicit request, several partially reversing the
  previous one — if you're comparing against an old screenshot, this is why):
  1. Bed/Bath column added to both the admin (create/edit form + read-only Show page) and public detail page
     payment-plan tables.
  2. Then **removed again from all of the above** (admin form input row, admin Show page's two tables, public
     detail page's two tables) — kept only in the underlying data/model, not displayed anywhere. **The
     backend `mount()`/`save()` round-trip logic was deliberately left in place** even after the admin form's
     visible fields were deleted, specifically so existing bedroom/bathroom data on a project survives an
     unrelated edit-and-save without a visible field to re-enter it (verified via `Livewire::test()`: mount a
     project with bedrooms set, save with no form change, confirm the DB value is unchanged).
  3. "Area Options" wording (a section heading, an admin card title, a stat label, form field labels, and a
     delete-confirmation string) renamed to **"Size Options"** everywhere — the products aren't always land
     "area", so the phrase was misleading for villas/shops/apartments. The public detail page's "Area Options
     & Payment Plans" heading was simplified further, to just **"Payment Plans"**.
  4. Per-block plot-size cards on the public detail page: each named block (and the "No Specific Block" group)
     now renders in its own visually separate, full-width bordered card (`.pd-block-card`) instead of being
     stacked bare headings+tables inside one shared accordion body.
  5. That whole "Payment Plans" section was then pulled **out of** the shared Detail/Description/Amenities/
     Location accordion entirely, into its own standalone full-width `.pd-panel` sitting below the two-column
     main-content/sidebar row (spanning the full page width, not just the 8/12 main column) — two rounds of
     "make this full width" landed on two different meanings of "full width" (first: full width of its
     column; second: full width of the whole page), see the doc history if replicating this exact panel shape.
  6. **"Total Price" renamed to "Total Amount" and moved to be the last column** in every payment-plan table,
     both admin and public (previously it was the 3rd column, right after Size/Category).
  7. `Detail & Features`/`Description`/`Location` accordion items converted to **always-open, non-collapsible
     static panels** (new `.pd-static-header` class — same visual weight as `.accordion-button` but no button
     element, no toggle, no chevron `::after`). Amenities/Brochure/Video remain real collapsible accordion
     items. A new "Video" accordion item (collapsed by default, like Brochure) renders `video_embed_url` in a
     Bootstrap `.ratio.ratio-16x9` iframe, only `@if ($project->video_embed_url)`.
  8. The "Back to All Projects" button moved from the bottom of the sidebar to directly below the gallery
     banner, then **replaced entirely with a `Home > Projects > {title}` breadcrumb** in that same spot — no
     back-link exists on the page at all anymore, only the breadcrumb.
  9. "More Projects" sidebar count changed 4 → 3 → 7 → 8 (`ProjectsController::show()`'s `$otherProjects`
     `->take(N)`) across several rounds of the same request.
- **Admin `Manager` top bar**: search input, type-filter dropdown, and "New Project" button used to sit
  together as one right-aligned group. Restructured so search stays anchored left (immediately after the page
  title) while the filter+button pair is pushed to the far right — achieved by wrapping search + (filter+button)
  in a `flex-grow-1 justify-content-between` container nested inside the existing title/`d-flex` row, not by
  touching the title block itself.

## 2026-08-16: real media for all 6 `RealProjectSeeder` projects, downloaded into the app

All 6 real projects (Blue World City, Kingdom Valley, Lakeshore City, Seventeen Villas, Faisal Town Phase 2,
ESMR Heights) started with **zero** cover/gallery images and no `video_url`, despite having real developer
websites. Fetched via 6 parallel background agents (one per project, `general-purpose` type — this kind of
"go find and download real assets from N independent external websites" task parallelizes cleanly since each
project's research is fully independent).

- **Technique**: `Spatie\MediaLibrary`'s `Model::addMediaFromUrl($url)->toMediaCollection($name)` downloads
  and attaches directly from a remote URL server-side — no manual `curl`-to-disk-then-`addMedia()` needed.
  Each agent was told to `curl -sI` verify a candidate URL is actually reachable and an `image/*` content-type
  *before* calling `addMediaFromUrl`, to avoid feeding it a 404/HTML error page.
- **Real gotcha, hit by 3 of the 6 agents independently**: PHP's default CLI `memory_limit` (128M) is
  frequently too small for Spatie's GD-based `thumb` conversion generation on large source photos (multi-MB,
  large-pixel-dimension real estate photography/renders) — throws a fatal "Allowed memory size exhausted"
  **during the conversion step**, after the base media row + original file have already been saved. Net
  effect: the attach silently "half-succeeds" — original file present and correct, `thumb` conversion missing,
  and in a few cases a partial/duplicate media row from the crashed attempt still sitting in the gallery.
  **Fix used everywhere this came up**: re-run the specific failed command with a higher limit
  (`php -d memory_limit=512M artisan tinker --execute="..."` or `1024M`), then check for and delete any
  duplicate media row the crashed attempt left behind (`Media::find($id)->delete()`). One straggler thumb
  conversion was still missing after all 6 agents finished (final verification sweep found 48 originals but
  47 thumbs across the properties work done the same session, see module-03) — regenerated directly via
  `app(\Spatie\MediaLibrary\Conversions\FileManipulator::class)->createDerivedFiles($media)` run under a
  raised memory limit, rather than deleting/re-adding the media. **If you ever see `addMediaFromUrl` "succeed"
  but a listing card shows a broken/missing thumbnail, check for exactly this** before assuming the URL itself
  was bad.
- **Video sourcing was deliberately conservative**: agents were told to leave `video_url` unset rather than
  guess when they found a *plausible* YouTube video but couldn't confirm the uploader was the actual developer
  (vs. a third-party real-estate vlogger/reseller channel, common in this space) — checked via YouTube's public
  oEmbed endpoint for the uploading channel's name. Only **Blue World City** ended up with a `video_url` (a
  video embedded directly on their own homepage, confirmed via oEmbed as published by
  "Blue World City Official"); the other 5 have none. **Don't assume a project with no video means the search
  failed** — for most of them it means no confidently-official video exists, which was checked, not skipped.
- **ESMR Heights' real site turned out not to be `faisalhillsislamabad.com.pk`** (that domain is the generic
  Faisal Hills township site and doesn't mention ESMR at all) — the agent found the actual joint-developer site,
  `etimaadinternational.com`, via web search and sourced images from there instead. Worth knowing if you ever
  need to re-verify or re-fetch this project's assets.

## Tests

**None.** Every piece of this module (creation with blocks+plot sizes, the block-index remapping on removal,
the Manager's toggle/delete actions, the Show page's per-block grouping) was verified via ad hoc
`Livewire::test()` scratch scripts run through `php artisan tinker`, checked, and then the seeded/test rows
cleaned up — not committed as PHPUnit test files. If you want real coverage, the natural cases mirror
`PropertyFormTest`: creation with nested blocks/plot sizes, the block-removal remapping specifically (it's
the one place a subtle off-by-one could silently corrupt data), the Manager's search/filter/toggle/delete
actions, and the Show page rendering the right block groupings.
