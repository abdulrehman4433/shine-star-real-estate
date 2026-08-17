# Module 2: Property Categories & Types

**Status:** Done. **Acceptance:** admin manages categories/sub-categories and types; reflected on frontend
filters (stub). ✅ Verified live via browser (create/toggle-active on the real Livewire pages, zero console
errors) + 12 automated tests at the time.

> **⚠️ 2026-08-05 update: the standalone admin pages described below were removed.** The rest of this doc
> (schema, models, drag-drop reorder mechanics) is still accurate as a description of the **data layer** —
> `PropertyCategory`/`PropertyType` still work exactly as described. What changed is **how rows get
> created**: `Admin\Categories\Manager` (`/admin/categories`) and `Admin\Types\Manager` (`/admin/types`) —
> the Livewire components, their Blade views, their routes, and their sidebar links — were all deleted.
> Categories and types are now created via **inline quick-add directly on the Property form**
> (`App\Livewire\Frontend\Properties\PropertyForm::quickAddCategory()`/`quickAddType()`): a small text input
> + "+ Add" button next to each `<select>`, which creates the row (category: always **top-level**, no parent
> picker in this quick-add flow; type: flat, same as before) and immediately selects it. `CategoryManagerTest`
> and `TypeManagerTest` (mentioned in the original Tests section below) were deleted along with the classes
> they tested — they were failing with `ErrorException: include(...Admin/Types/Manager.php): Failed to open
> stream` etc. once the components no longer existed. See the root `README.md`'s "Simple admin-editable
> lookup tables..." convention bullet for the general pattern, and module-14 (Projects) for why this
> direction was chosen. **If you need to reintroduce category/type reordering, active-toggling, deleting, or
> sub-category creation through a UI, that capability is currently gone** — this quick-add flow only
> supports creating new rows, nothing else.

## What was built

- **`property_categories` table:** self-referencing `parent_id` (`nullOnDelete` — deleting a parent
  promotes its children to top-level, doesn't cascade-delete them), `name`/`slug`, `icon` (CSS class
  string, e.g. `bi bi-house`), `order`, `is_active`. Image via medialibrary (`image` singleFile collection),
  not a DB column.
- **`property_types` table:** flat list (no parent), same `name`/`slug`/`order`/`is_active` shape. Seeded
  with Rent, Sale, Lease, Commercial (`PropertyTypeSeeder`).
- **Models:** `PropertyCategory` (`parent()`, `children()` ordered by `order`, `scopeActive`, `scopeRoots`,
  `descendantIds()` for circular-parent prevention), `PropertyType` (`scopeActive`). Both use `HasSlug`.
- **Admin CRUD as full-page Livewire components** (not plain controllers):
  `App\Livewire\Admin\Categories\Manager` (`/admin/categories`) and `App\Livewire\Admin\Types\Manager`
  (`/admin/types`). Both: create/edit modal, delete, active toggle, **drag-and-drop reorder**.
- **Drag-drop reorder mechanism** (also reused in Module 3 for amenities): `@alpinejs/sort` plugin.
  Container: `x-sort="(id, position) => $wire.reorder(id, position)"`. Item: `x-sort:item="{{ $id }}"`.
  Handle: `x-sort:handle` on a small drag-icon span. Livewire `reorder($itemId, $position)` method: fetch
  siblings ordered by `order` (same `parent_id` for categories), remove the moved item, splice it back in
  at `$position`, re-save sequential `order` values for anything that changed.
- **This module is where the Livewire+Alpine bundling problem was found and fixed** — see the root
  `docs/modules/README.md` conventions section. Short version: don't `npm install alpinejs` and call
  `Alpine.start()` yourself; import `{ Livewire, Alpine }` from Livewire's own ESM bundle instead.
- **Frontend stub:** `Frontend\PropertiesController@index` (superseded in Module 3 by a real Livewire
  listing component) showed category/type dropdowns as a non-functional stub — that controller method is
  gone now; see Module 3 doc.

## Gotchas / things to know

- Nested category dropdown pattern (used again in Module 3's property form and frontend filters): fetch
  `roots()` with `children` eager-loaded, render as `<option>` + indented `<option>` per child.
- ~~Admin sidebar links to `route('admin.categories.index')` / `route('admin.types.index')`~~ — **removed
  2026-08-05**, see the update banner at the top of this doc.

## Tests

**Deleted 2026-08-05** — `tests/Feature/Admin/{CategoryManagerTest,TypeManagerTest}.php` tested
`Admin\Categories\Manager`/`Admin\Types\Manager`, both of which no longer exist; the tests were failing
with `include(...): Failed to open stream` for every case. If quick-add ever grows real CRUD again, the
original coverage (below) is still the right shape to restore:
CRUD, validation (name required/unique), circular-parent prevention, active toggle, delete-promotes-children,
reorder persistence.
