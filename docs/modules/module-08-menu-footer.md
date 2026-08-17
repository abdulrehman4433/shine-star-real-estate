# Module 8: Dynamic Menu & Footer Builder

**Status:** Done + enhanced (2026-07-28). **Acceptance:** admin adds/reorders/nests menu items and footer widgets; frontend
updates without code changes. ✅ Footer now has top-level settings (name, status, column count) — default 3 columns,
max 4, inactive footer hidden on frontend. Verified via live browser testing.

**Note (2026-08-14):** `footer_settings` has grown well beyond `name`/`status`/`columns` since the section
above was written — it now also has `company_name`, `copyright_text`, `copyright_tagline`, `social_links`
(JSON array of `{platform,url,icon}`), four `show_*` boolean toggles, and four `position_*`
(`left`/`right`) fields controlling a two-column bottom bar (brand/tagline/social icons/copyright, each
independently shown/hidden and placed left or right) below the widget columns — all editable from the same
`/admin/footer` page and rendered by `frontend/partials/footer.blade.php`. This doc's original "what was
built" section below wasn't updated to describe this; treat the model/view/Livewire component as the source
of truth for the current field list.

## 2026-08-14 fix: `/admin/footer` 500ing with a `TypeError` on `$footerSocialLinks`

**Real bug, reported by the user as an `Internal Server Error` on `/admin/footer`:**
`TypeError: Cannot assign string to property App\Livewire\Admin\Footer\Manager::$footerSocialLinks of type array`.

Root cause: `FooterSetting::$casts` declares `social_links` as `'array'`, which already JSON-encodes on save
and JSON-decodes on read. `database/seeders/FooterWidgetSeeder.php` called `json_encode([...])` itself
*before* assigning to `'social_links'` in `updateOrCreate(...)` — so the value got encoded **twice**: once by
the seeder, once by the cast on save. The column ended up holding a JSON string whose content was itself an
escaped JSON string (`"[{\"platform\":\"facebook\",...}]"`, quotes and all), not a JSON array. Reading it back
through the `array` cast decodes that outer layer and returns a plain **string** (the inner JSON text), not an
array — which is what blew up `Manager::mount()`'s `$this->footerSocialLinks = $this->footerSetting->social_links ?? []` against the strictly-typed `array $footerSocialLinks` property. This also meant the frontend
footer's social icons were silently broken (a one-item collection wrapping the raw string, rendering one
malformed icon instead of the real three) rather than erroring outright, since `footer.blade.php` reads the
same column more defensively.

**Fix:**
- `FooterWidgetSeeder.php` now assigns a plain PHP array (removed the redundant `json_encode()`)  — the model's
  cast is the only thing that should ever encode this column.
- The already-corrupted live row was repaired directly (decoded the double-encoded string back into a real
  array and re-saved through the model so the cast encodes it exactly once), and the `footer_widgets` cache
  was cleared.
- **Standing rule:** never call `json_encode()`/`json_decode()` by hand on an attribute the model already
  casts as `'array'`/`'json'` — assign/read the plain PHP value and let the cast do it once. If you write a
  seeder or tinker script that sets a cast `array` column, pass the array literal directly, the same way
  `Admin\Footer\Manager::saveFooterSettings()` already correctly does for this exact column.

## What was built (original)

- **`menus` table:** `name`, `location` (`header`/`footer` — `App\\Enums\\MenuLocation`). Multiple menus can
  share a location; the frontend only ever renders the first one found per location (see `Menu::cachedTree()`).
- **`menu_items` table:** `menu_id` (cascade), `parent_id` self-referencing (`nullOnDelete` — deleting a
  parent promotes its children to top-level, same pattern as `property_categories` in Module 2), `page_id`
  nullable FK to `pages` (Module 7 — link a menu item straight to a CMS page), `label`, `url` (custom link,
  used when `page_id` is null), `target` (`_self`/`_blank`), `order`, `is_active`.
  `MenuItem::resolvedUrl()` is the single place that decides what a menu item actually points to: if
  `page_id` is set it resolves to `url($page->slug)`, otherwise falls back to the raw `url` column (or `#`).
- **`footer_widgets` table:** `title` (nullable), `type` (`text`/`links` — `App\\Enums\\FooterWidgetType`),
  `content` (JSON — `{body}` for text widgets, `{links: [{label,url}, ...]}` for link widgets), `column`
  (0–3, which footer column it renders in), `order` (position within that column), `is_active`.
- **Admin — `Admin\\Menus\\Manager`** (`/admin/menus`): tabs across the top switch between existing menus,
  "+ Add Menu" creates a new one (name + location), then within the selected menu: nested item tree with
  **drag-drop reorder** (same `x-sort` pattern as every other admin manager — see root README), add/edit
  item modal (label, link-to-page dropdown OR custom URL, parent dropdown for nesting, target, visibility
  toggle), delete.
- **Admin — `Admin\\Footer\\Manager`** (`/admin/footer`): original version had four fixed columns (0–3) side
  by side, each with its own `x-sort` reorder scope, a shared add/edit modal. Enhanced 2026-07-28 — see
  below.
- **Frontend rendering is 100% dynamic now** — header (`resources/views/frontend/partials/header.blade.php`)
  and footer (`.../footer.blade.php`) partials no longer have any hardcoded nav links or footer columns.
  Both are populated via **View Composers** registered in `AppServiceProvider::boot()`.
- **Caching:** `Menu::cachedTree($location)` and `FooterWidget::cachedColumns()`.
- **Header dropdown rendering:** items with children render as a Bootstrap dropdown driven by local Alpine.
- **`MenuSeeder`:** one "Main Menu" (header location) — Home, Properties (with nested dropdown), About Us,
  Contact.
- **`FooterWidgetSeeder`:** original version had one widget per column (About, Quick Links, Legal, Contact).

## 2026-07-28 enhancement: footer-level settings

The footer builder was upgraded with a top-level configuration layer, similar to the header template system.

### New model and migration

- **`footer_settings` table** (`2026_07_28_010000_create_footer_settings_table.php`): `id`, `name` (string,
  default "Main Footer"), `status` (string: `active`/`inactive`, default `active`), `columns` (unsigned
  tinyint, default `3`), timestamps. A default row is inserted by the migration.
- **`App\\Models\\FooterSetting`** — simple model with `$fillable` (`name`, `status`, `columns`),
  `$casts` (`columns` → integer), and two static helpers:
  - `FooterSetting::current()` — returns the first active footer setting, or the first one found if none
    is active, or `null` if the table is empty.
  - `isActive()` — returns `true` when `status === 'active'`.

### Footer Manager UI changes

The `/admin/footer` page now has two sections:

1. **Footer Settings card** at the top with three fields:
   - **Name** — a name/identifier for the footer (text input)
   - **Status** — Active/Inactive toggle (select dropdown). When Inactive, the entire footer is hidden
     on the frontend.
   - **Columns** — dropdown with 2, 3, or 4 columns (default 3). Changing this dynamically adjusts the
     column layout below.
   - "Save Settings" button at top-right, `session()->flash('status', 'footer-settings-saved')` on success.
2. **Widget columns** — dynamically renders the configured number of columns using `col-md-{{ 12 / $footerColumns }}`
   for responsive widths. The widget modal's column dropdown only shows columns within the configured range.

### Livewire component changes (`Admin\\Footer\\Manager`)

- Added `mount()` method that loads `FooterSetting::current()` (with fallback creation) and initializes
  `$footerName`, `$footerStatus`, `$footerColumns`.
- Added `saveFooterSettings()` method with validation:
  - `footerName`: `required|string|max:255`
  - `footerStatus`: `required|in:active,inactive`
  - `footerColumns`: `required|integer|min:2|max:4`
- `public FooterSetting $footerSetting` property typed as the model.

### Frontend footer changes (`resources/views/frontend/partials/footer.blade.php`)

- Wrapped the widget columns section in a check: `@if ($footerColumns->isNotEmpty() && $footerSetting && $footerSetting->isActive())`
- Column loop uses `$footerSetting->columns` instead of hardcoded `0..3`:
  ```blade
  @for ($col = 0; $col < $footerSetting->columns; $col++)
  ```
- Column widths are dynamic: `class="col-md-{{ 12 / $footerSetting->columns }}"`
- The copyright/social links section always renders regardless of footer status.

### View Composer update (`AppServiceProvider`)

The footer View Composer now also passes `$footerSetting = \\App\\Models\\FooterSetting::current()`.

### Seeder update (`FooterWidgetSeeder`)

- Creates or updates a `FooterSetting` record (name: "Main Footer", status: "active", columns: 3).
- Widgets now match 3 columns (About, Quick Links, Contact) instead of the original 4.
- Idempotent — guarded by `if (FooterWidget::query()->exists()) return;`.

### Admin view improvements

- Widget list items now show an On/Off badge (green/gray) for quick visibility status.
- Toggle button uses Bootstrap icons (`bi-eye`/`bi-eye-slash`) instead of text labels.
- Delete button includes the widget name in the confirmation dialog.
- Add widget button uses `bi-plus-lg` icon.

## Gotchas / things to know

- **Menu item dropdowns only go one level deep** in the current header partial.
- **`FooterSetting::current()` can return null** — the Footer Manager's `mount()` has a fallback
  (`FooterSetting::current() ?? FooterSetting::create(['name' => 'Main Footer', 'status' => 'active',
  'columns' => 3])`), and the frontend footer checks `$footerSetting && $footerSetting->isActive()`.
- **Cache invalidation:** `saveFooterSettings()` calls `Cache::forget('footer_widgets')` — if you add
  a new write path in the Footer Manager, forget the same cache key.
- **Columns are 2–4 range** — validated at the Livewire level (`min:2|max:4`). If you need 1 or 5+
  columns in the future, update both the validation rule and the frontend Bootstrap grid classes.
- The `saveFooterSettings()` method uses `session()->flash('status', 'footer-settings-saved')` — the
  view checks for this to show a success alert. If you add additional save actions, flash a different
  status key to avoid conflicts.
- **This footer's own `social_links` JSON field (name/URL/icon rows edited right here in the Footer Manager)
  is completely independent of the separate, admin-manageable `SocialLink` model/table from Module 11 —
  which was removed 2026-08-05.** Before removal, `footer.blade.php` preferred this footer-level field and
  only fell back to `SocialLink::cachedActive()` when it was empty; that fallback line was deleted along
  with everything else in Module 11, so `$bottomSocialLinks` is now always `collect($footerSetting
  ->social_links ?? [])` with no fallback source. **This module's own feature was never touched and needs no
  changes** — it's called out here only so nobody confuses the two when reading Module 11's doc.

## Tests

None added this module. What was verified originally: HTTP smoke tests (homepage renders seeded nav,
footer renders seeded widgets, admin pages load), plus full 75-test suite. The 2026-07-28 enhancement
was verified via live browser testing (changing column count dynamically, toggling active/inactive,
saving footer settings, verifying frontend reflects changes).
