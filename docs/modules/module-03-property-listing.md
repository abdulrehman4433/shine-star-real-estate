# Module 3: Property Listing (Core)

**Status:** Done. **Acceptance:** agent submits → pending → admin approves → visible with working filters
and map. ✅ Verified via 64 automated tests at the time (a live browser walkthrough was started but cut
short at the user's request — automated coverage is the source of truth here).

> **2026-08-05 updates (three separate changes, all below in detail):**
> 1. `Admin\Amenities\Manager` (`/admin/amenities`) was **removed** — amenities are now created via inline
>    quick-add on `PropertyForm` itself, same pattern as Module 2's categories/types. `AmenityManagerTest`
>    was deleted (was failing: the class it tested no longer exists).
> 2. `Admin\Properties\Manager`'s list page changed from a `<table>` to a **responsive card grid**
>    (`row-cols-1` → `row-cols-xl-5`), `->paginate(20)` (was 15). Approve/Reject/Expire moved from an inline
>    button group into a `bi-three-dots` dropdown per card (not enough width for 4+ buttons at 5 columns).
> 3. A new **`Admin\Properties\Show`** (`/admin/properties/{property}`, `admin.properties.show`) read-only
>    detail page was added — reachable via a "View" button on the list. Shows everything the edit form
>    collects (category/type/price/owner, address+read-only map, amenities, custom features, media, SEO
>    fields, timestamps, rejection reason if any) without the ability to change it. This is **separate from**
>    the existing public `properties.show` route (`Frontend\PropertiesController@show`) — the admin one shows
>    internal-only fields (owner, rejection reason, raw SEO values) regardless of moderation status; the
>    public one only ever shows `approved()` properties through the public-facing template.

## What was built

- **`properties` table:** `user_id`/`category_id`/`type_id` FKs (all `restrictOnDelete` — can't delete a
  category/type/user while properties reference it), `title`/`slug`, `description`, `price` + `price_type`
  (`fixed`|`negotiable`, `App\Enums\PropertyPriceType`), `status` (`pending`|`approved`|`rejected`|`expired`,
  `App\Enums\PropertyStatus`) + `rejection_reason`, `address`/`city`, `lat`/`lng` (decimal 10,7),
  `size`/`bedrooms`/`bathrooms`, `is_featured`, `expiry_date`. Index on `(status, city)`.
- **`property_amenities`** (lookup table, same shape as property_types: name/slug/order/is_active) **+**
  **`property_amenity_property`** pivot. **`property_features`** (per-property custom key/value specs, NOT
  a shared lookup — just `property_id`/`name`/`value` rows).
- **`Property` model:** `HasSlug` (route key is `slug` — `getRouteKeyName()`), `HasMedia` with two
  collections (`featured` singleFile, `gallery` multiple) + a `thumb` conversion (480×320 crop, non-queued).
  Relations: `owner()`, `category()`, `type()`, `amenities()` (belongsToMany), `features()` (hasMany).
  Helpers: `scopeApproved`, `statusEnum()`, `isPending()/isApproved()/isExpired()`.
- **`App\Policies\PropertyPolicy`:** `view()` (approved = public; pending/rejected/expired only visible to
  owner or admin/super-admin — `$user` param is nullable so guests can view approved ones), `create()`
  (agent|agency only), `update()`/`delete()` (owner or admin), `moderate()` (admin/super-admin only, used
  by the admin manager for approve/reject/feature/expire).
- **Agent-facing Livewire components** (`app/Livewire/Frontend/Properties/`):
  - `PropertyForm` — create/edit. Big form: basic info, **Leaflet map picker** (click to drop pin, wrapped
    in `wire:ignore` + Alpine `x-data`, calls `$wire.setLocation(lat, lng)`), specs, amenities checkboxes,
    custom-features repeater (add/remove rows bound to `customFeatures.{index}.name/value`), featured image
    + gallery upload (`WithFileUploads`). **Editing always resets `status` back to `pending`** (per spec) —
    this happens unconditionally in `save()`, since only agents use this form (admin moderation is a
    separate component that never touches these fields).
  - `MyListings` — agent's own listings, paginated, delete (policy-checked).
- **Admin-facing Livewire components:**
  - `Admin\Properties\Manager` (`/admin/properties`) — status filter tabs, approve/reject (reject opens a
    reason modal, `rejection_reason` required)/feature-toggle/expire-now actions. Guards every action with
    `Gate::authorize('moderate', Property::class)` in addition to route middleware (defense in depth).
    **As of 2026-08-05 this list renders as a card grid, not a table** — see the update note at the top of
    this doc.
  - `Admin\Properties\Show` (`/admin/properties/{property}`) — **added 2026-08-05**, read-only detail page,
    see the update note at the top of this doc.
  - ~~`Admin\Amenities\Manager` (`/admin/amenities`)~~ — **removed 2026-08-05**. Originally the same
    CRUD+reorder pattern as categories/types (Module 2), since agents need a source list of amenities to
    pick from; not explicitly asked for in the spec but required for the amenities pivot to be usable.
    Amenities are now quick-added directly from `PropertyForm` — see the update note at the top of this doc
    and the root `README.md`'s "inline quick-add" convention.
- **Frontend public components:**
  - `Frontend\Properties\Listing` (`/properties`, full-page Livewire) — grid/list toggle, live filters
    (category, type, min/max price, bedrooms, city — all `#[Url]`-bound so filters persist in the query
    string and are shareable/bookmarkable), pagination. Only `approved()` properties ever show here.
  - `Frontend\PropertiesController@show` (`/properties/{property:slug}`) — detail page, policy-gated via
    `$this->authorize('view', $property)`. Image carousel, amenities/features, **read-only Leaflet map**
    (again `wire:ignore` + Alpine, no Livewire interactivity needed since it's a plain Blade view, not a
    Livewire component).
- **Leaflet integration:** `leaflet` npm package. `resources/js/app.js` imports `L`, fixes the
  Vite-bundled marker icon paths (`delete L.Icon.Default.prototype._getIconUrl` +
  `L.Icon.Default.mergeOptions({...})` pointing at the imported PNG URLs — without this the default pin
  icons are broken). `window.L = L` so plain `x-data` blocks can use it without importing per-view.
- **Cloudinary:** `cloudinary-labs/cloudinary-laravel` installed, `cloudinary` disk added to
  `config/filesystems.php` (driven by `CLOUDINARY_URL` env var). Medialibrary's disk is
  `env('MEDIA_DISK', 'public')` — defaults to local for dev, flip to `cloudinary` once you have real
  credentials. No code changes needed to switch, just `.env`.
- **Sample data:** `PropertyAmenitySeeder` (10 common amenities), `PropertySeeder` (4 sample listings —
  2 approved incl. 1 featured, 1 pending, 1 rejected with a reason — owned by the demo agent/agency
  accounts, with amenities attached).
- **2026-08-14: `FeaturedPropertyDemoSeeder`** added (registered right after `PropertySeeder`) — 12 more
  approved listings purely so the homepage's "Featured Property For Sale" section
  (`HomeController::renderFeaturedPropertiesSection()`, `take(6)`) has enough content to fill its 3-column
  grid; `PropertySeeder`'s 2 approved rows alone left it visibly sparse. Deliberately a separate seeder
  from `PropertySeeder` — that one exists to demonstrate the moderation workflow (one row per status), this
  one is just "give the homepage something to show". 4 of the 12 are `is_featured`. Guarded by checking for
  its own marker text in `description` (`PropertySeeder`'s guard is a plain `firstOrCreate` per title,
  which doesn't fit here since none of these titles need to be individually unique-checked against reruns).
- **2026-08-16: all 16 demo properties (4 + 12 above) got real photos** — both seeders create rows with zero
  attached media, which had gone unnoticed until explicitly requested. Fetched via 4 parallel background
  agents, grouped by visual theme rather than 1-per-property (apartments/studios/penthouse — 7 properties;
  villas — 3; houses — 2; commercial/office/shop/warehouse — 4), each sourcing real, freely-licensed photos
  from Pexels/Pixabay matching that group's theme and attaching 1 `featured` + 2 `gallery` images per property
  via `addMediaFromUrl()`. **Grouped by title content, not the `category` field** — several seeded rows have a
  title/category mismatch (e.g. a property titled "Furnished Studio Near Liberty Market" has `category` =
  "Villa"; another titled "Cozy Villa with Private Garden" has `category` = "Warehouse" — plausibly a seeder
  typo, not corrected as this was out of scope), and the request was for images relevant to "property title"
  specifically, so titles were the grouping signal, not the (partly wrong) category. Hit the same
  `addMediaFromUrl` + PHP `memory_limit` conversion-generation gotcha module-14's real-project media work hit
  the same day — see that doc's entry for the fix pattern (higher `-d memory_limit`, then check for/delete any
  duplicate media row a crashed attempt left behind). One straggler missing `thumb` conversion (out of 48 total
  images across all 16 properties) was caught in a final verification sweep and regenerated directly via
  Spatie's `FileManipulator::createDerivedFiles()` rather than re-adding the media.

## Gotchas / things to know

- Full-page Livewire components in this module all end `render()` with
  `->extends('frontend.layouts.app')->section('content')` (or `admin.layouts.app` for admin ones) — see
  root README conventions note on why `->layout()` doesn't work with our Blade-style layouts.
- `PropertyForm`'s `mount(?Property $property = null)` pattern: create route has no `{property}` segment,
  edit route does — Livewire's implicit route-model-binding resolves it automatically by matching the
  mount parameter name to the route segment.
- The category `<select>` in `PropertyForm` and the `Listing` filter both use the same
  roots-with-eager-loaded-active-children pattern from Module 2.
- **2026-08-14: `frontend/properties/show.blade.php` redesigned** — same reference-file-derived visual
  system (panel/accordion, `.pd-` prefixed, page-scoped CSS custom properties) as module-14's public
  Project detail page redesign the same day; see that doc's entry for the full reasoning on what was kept
  vs. dropped from the reference. Property-specific notes:
  - **All pre-existing real functionality was preserved as-is**, just restyled: `FavoriteButton` Livewire
    component (moved into a top-right overlay on the gallery), `InquiryForm` Livewire component (unchanged,
    just wrapped for spacing — its own internal `.card` styling was deliberately left alone), the
    `@auth`/`@guest` branch for "Chat with Owner" vs. embedded `GuestChatBox`, and the Leaflet map.
  - **New: a real, working payment calculator** in the sidebar (`Math.pow`-based amortization in a plain
    Alpine `x-data`, pre-filled with the property's own price and a 20% default down payment) — gated
    `@if ($isPurchaseType && $property->price > 0)` where `$isPurchaseType` excludes `Rent`/`Lease` type
    properties, since a mortgage-style calculator makes no sense against a monthly rental figure. This is
    the Property equivalent of Module 14's "Area Options & Payment Plans" table, but built differently
    on purpose: Projects have real stored installment-plan data to render, Properties don't, so a live
    client-side estimate (clearly labeled "Estimate only") is the honest equivalent rather than either
    fabricating fake financing data or copying Project's table verbatim.
  - **New: a "More Properties" sidebar** (`PropertiesController::show()` now also queries up to 4 other
    `approved()` properties, preferring the same city and falling back to just-latest if none share it).
  - **Dropped from the reference** (same reasoning as module-14): Property Video, Floor Plan, Rating, Write
    a Review, and Reviews accordion sections — no video/rating/review system exists for `Property`.
- **2026-08-14 fix: category filter didn't include sub-categories.** `WithPropertyFilters::applyFilters()`
  did a bare `where('category_id', $this->category)` — an exact match. Selecting a **parent** category in
  the filter dropdown (e.g. "Commercial") silently returned zero matches for any property actually filed
  under one of its children ("Office"/"Shop"/"Warehouse"), since properties are only ever assigned a leaf
  category, never the parent itself. Fixed via a new `categoryIdsForFilter()` helper that resolves the
  selected category plus `PropertyCategory::descendantIds()` (already existed, previously only used for
  circular-parent prevention in the admin quick-add flow) into a `whereIn('category_id', [...])`. Selecting
  a leaf category is unaffected — `descendantIds()` returns `[]` for a category with no children, so the id
  list is just the one selected, identical to the old exact-match behavior. Since `WithPropertyFilters` is
  shared by `Frontend\Properties\Listing` (public `/properties`), `MyProperties`, and `MyListings`, the fix
  applies to the category filter everywhere it appears, not just the public listing page.

## Tests

`tests/Feature/Properties/{PropertyFormTest,PropertyListingTest,PropertyDetailTest,MyListingsTest}.php`,
`tests/Feature/Admin/PropertyManagerTest.php` — creation, validation, amenities/features persistence, map
picker (`setLocation`), edit-resets-to-pending, ownership authorization, all 6 filter dimensions,
pagination, moderation actions, visibility rules for guest/owner/admin on non-approved properties.
**`tests/Feature/Admin/AmenityManagerTest.php` was deleted 2026-08-05** along with `Admin\Amenities\Manager`
itself (see the update note at the top of this doc). **No automated coverage exists yet for**: the
`quickAddCategory()`/`quickAddType()`/`quickAddAmenity()` methods, the card-grid list page, or
`Admin\Properties\Show` — all verified only via `Livewire::test()` scratch scripts + manual review during
that change, not committed test files.
