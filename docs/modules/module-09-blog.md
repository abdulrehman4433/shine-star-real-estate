# Module 9: Blog Module

**Status:** Done. **Acceptance:** admin publishes a post with category/tags; visible on frontend with
working filters. ✅ Verified via HTTP smoke tests (listing + single post render, category/tag filtering
narrows results correctly, draft posts 404 for guests, admin pages load) + full existing test suite
(75/75). **No new automated tests were written** — per the same "move fast" instruction as Modules 5–8;
user is testing this module manually.

**Note:** the doc's "Optional: moderated comments" line item was intentionally skipped — same reasoning as
skipping Module 4's optional saved-search/alerts: explicitly optional, not covered by acceptance criteria.

> **2026-08-05 updates (three separate changes):**
> 1. `Admin\Blog\Categories\Manager` (`/admin/blog/categories`) and `Admin\Blog\Tags\Manager`
>    (`/admin/blog/tags`) were **removed** — categories/tags are now created via inline quick-add on the
>    post form itself (`Admin\Blog\Posts\Form::quickAddCategory()`/`quickAddTag()`), same pattern as Module
>    2/3's categories/types/amenities. No test files existed for these two managers, so nothing needed
>    deleting there (unlike Module 2/3's equivalents).
> 2. `Admin\Blog\Posts\Manager`'s list changed from a table to the same responsive **card grid** used by
>    Properties/Projects, `->paginate(20)` (was 15).
> 3. The post form's **Tags field was a native `<select multiple size="3">`** — replaced with a
>    Bootstrap-dropdown-styled checkbox panel. See "Tags field: dropdown + checkboxes" below.

## What was built

- **`blog_categories` table:** flat (no nesting, unlike `property_categories`) — `name`/`slug`, `order`,
  `is_active`. Same CRUD+reorder pattern as every other admin manager.
- **`blog_tags` table:** just `name`/`slug` — no `order`/`is_active`, tags don't need manual ordering.
- **`blog_posts` table:** `category_id` nullable (`nullOnDelete`), `author_id` (FK users, `restrictOnDelete`
  — can't delete a user who has authored posts), `title`/`slug`, `excerpt` (short card-preview summary, a
  reasonable small addition beyond the doc's literal column list since listing cards need something to
  show), `content` (longtext, HTML from the rich text editor), `status` (`App\Enums\BlogPostStatus`:
  draft/published), `published_at` (nullable — **supports scheduling**: a post can be `status=published`
  with a future `published_at` and it still won't appear on the frontend until that time passes — see
  `BlogPost::scopePublished()`/`isPublished()`).
- **`blog_post_tag`** pivot (many-to-many, unique on the pair).
- **Rich text editor: CKEditor 5** (`@ckeditor/ckeditor5-build-classic` — genuinely free, self-hosted, no
  API key/cloud nag, matching the doc's "TinyMCE/CKEditor, both free" note). Bundled into `app.js` exactly
  like Leaflet was in Module 3/7 (`window.ClassicEditor = ClassicEditor`). Wired into the post form via the
  same `wire:ignore` + Alpine `x-data` pattern used for maps: `ClassicEditor.create(...)` on mount, and
  `editor.model.document.on('change:data', ...)` pushes the HTML back into the Livewire `content` property
  via `$wire.set(...)`. **This one has a debounce** (400ms via `setTimeout`/`clearTimeout` in the Alpine
  scope) that the map integrations didn't need, since CKEditor's `change:data` event fires on every
  keystroke — without debouncing, every keystroke would trigger a full Livewire round-trip.
- **Admin:**
  - ~~`Admin\Blog\Categories\Manager` (`/admin/blog/categories`)~~ / ~~`Admin\Blog\Tags\Manager`
    (`/admin/blog/tags`)~~ — **both removed 2026-08-05**, see the update note at the top of this doc.
    Originally: categories were the identical shape to `Admin\Types\Manager`/`Admin\Amenities\Manager`
    (modal CRUD, active toggle, drag-drop reorder); tags were simpler (no reorder/active toggle, just
    create/rename/delete, rendered as pill-badges showing post count via `withCount('posts')`).
  - `Admin\Blog\Posts\Manager` (`/admin/blog/posts`) — list, status filter, publish/unpublish toggle
    (`toggleStatus()` sets `published_at` to now() the first time a post is published, but preserves an
    existing `published_at` on subsequent toggles so republishing doesn't reset a scheduled/original date),
    delete. **As of 2026-08-05 this renders as a card grid** (see the update note at the top of this doc),
    not the original table.
  - `Admin\Blog\Posts\Form` (`/admin/blog/posts/create`, `/admin/blog/posts/{post:id}/edit` — bound by id
    for the same reason as the Module 7 page builder: editing a post's slug shouldn't break the edit URL)
    — title, category select **+ quick-add** (2026-08-05), tags field **+ quick-add**, excerpt, CKEditor
    content, status, optional `datetime-local` publish date (blank = publish immediately if status is
    Published), featured image upload. See "Tags field: dropdown + checkboxes" below for the tags UI.
- **Frontend:**
  - `Frontend\Blog\Listing` (`/blog`, full-page Livewire) — paginated grid (9/page), category + tag
    filters (`#[Url]`-bound, shareable/bookmarkable query strings, same pattern as Module 3's property
    listing filters), only ever queries `published()` posts.
  - `Frontend\BlogController@show` (`/blog/{post:slug}`) — single post page: featured image, full HTML
    content (`{!! $post->content !!}` — trusted since only admins can write it), tag badges linking back to
    the filtered listing, **related posts** (`BlogPost::relatedPosts()` — same category, published,
    excludes self, limit 3). Same draft-preview-for-admin-only / 404-for-everyone-else rule as
    `PageController` and `PropertiesController` before it.
- **Routes:** `/blog` and `/blog/{post}` were added **above** the Module 7 catch-all `/{slug}` route (which
  must stay last) — see the root README conventions note on why route order matters here.
- **Nav:** the seeded header menu (Module 8) already existed from a prior run, so the seeder's
  "skip if items exist" idempotency guard meant simply re-running `MenuSeeder` would NOT add the new "Blog"
  item — it had to be inserted directly via `tinker` this one time. If you re-seed a fresh database from
  scratch, `MenuSeeder` now creates the Blog item correctly as part of its normal item list.
- **`BlogCategorySeeder`/`BlogTagSeeder`/`BlogPostSeeder`:** 3 categories, 4 tags, 3 posts (2 published —
  one with tags in "Market Trends", one in "Buying Guides" — 1 draft, so status filtering has something
  real to demonstrate).

## Gotchas / things to know

- **CKEditor's `change:data` handler needs debouncing** — don't copy the Leaflet `wire:ignore` pattern
  verbatim for text-input-heavy integrations without adding one; Leaflet only fires its callback on
  discrete user actions (a map click), CKEditor fires on every keystroke.
- **`BlogPost::isPublished()` checks both `status` AND `published_at`** — a post can be `status=published`
  in the DB and still not be "live" if `published_at` is in the future. Don't just check `status ===
  'published'` anywhere in new code; use `isPublished()` or the `published()` scope.
- **`Admin\Blog\Posts\Manager::toggleStatus()`** deliberately does NOT overwrite an existing `published_at`
  when re-publishing a previously-published-then-unpublished post — only sets it if it was previously null.
  If you want "republish" to always reset the date to now, that's a deliberate behavior change, not a bug
  fix.
- Bundle size grew noticeably this module (~2MB minified JS, up from ~670KB) because CKEditor 5's classic
  build is large — expected and acceptable for this dev/demo app; would be worth code-splitting
  (`import()` the editor only on the post-form route) if this were heading to production traffic.
- **Tags field: dropdown + checkboxes (2026-08-05).** The original `<select wire:model="tagIds" multiple
  size="3">` had two problems: (1) a cramped 3-row listbox that didn't match the Category `<select>`'s
  height in the same row, and (2) Livewire doesn't render a `selected` attribute on any `<option>` in the
  server HTML at all (confirmed by rendering an edit-mode form for a post with tags already attached and
  finding zero `selected` attributes in the raw output) — it relies entirely on client-side JS to reflect
  selection after hydration, which reads as "the tags don't show right" even though it's arguably "working
  as designed." Fixed with a Bootstrap dropdown whose trigger button is styled `.form-select` (so it looks
  identical to Category at rest) and whose panel is a list of individually `wire:model="tagIds"`-bound
  checkboxes (same reliable per-item binding the Amenities field already used elsewhere in this app):
  ```blade
  <div class="dropdown">
      <button type="button" class="form-select text-start text-truncate"
          data-bs-toggle="dropdown" data-bs-auto-close="outside">
          {{ $tags->whereIn('id', $tagIds)->pluck('name')->implode(', ') ?: 'Select tags...' }}
      </button>
      <div class="dropdown-menu w-100 p-2" style="max-height: 220px; overflow-y: auto;">
          @foreach ($tags as $tag)
              <div class="form-check">
                  <input type="checkbox" wire:model="tagIds" value="{{ $tag->id }}" ...>
                  ...
              </div>
          @endforeach
      </div>
  </div>
  ```
  `data-bs-auto-close="outside"` is required — the default `data-bs-toggle="dropdown"` behavior closes the
  panel on *any* click inside it, which would close it the instant you check a box.
- **2026-08-14: `/blog` listing redesigned** from a reference HTML file the user supplied (navy gradient
  header, rounded card grid, circular-pill pagination, gradient CTA banner) — same overall approach as
  module-03/module-14's detail-page redesigns the same day (page-scoped `.bl-` prefixed CSS custom
  properties, real data only, nothing fabricated). Specific to this page:
  - **All real functionality preserved**: `Listing`'s existing category/tag filters (`#[Url]`-bound,
    `wire:model.live`) and Laravel's Bootstrap-5 paginator (`{{ $posts->links() }}`) are untouched
    mechanically — only restyled (the category filter moved into the header's right-hand slot, the
    paginator's `.pagination`/`.page-link` markup re-skinned into circular pills via CSS, not replaced).
  - **The reference's fake "X Comments" meta-row item was dropped** — `BlogPost` has no comment system
    (per this doc's own "Optional: moderated comments" note, explicitly skipped) — replaced with the real
    author name instead, next to the real published date.
  - **`database/seeders/BlogPostDemoSeeder.php` added** (registered after `BlogPostSeeder`) — 7 more
    published posts with real, specific real-estate content (neighborhood selection, rent-vs-buy math,
    plot booking terminology, resale-value upgrades, rental agreements, construction-cost pricing,
    commercial-vs-residential investing) rather than lorem ipsum, so `/blog`'s 3-column grid (paginate 9)
    has enough content to fill a full page instead of the 2 rows `BlogPostSeeder` alone produces. Spread
    across the existing 3 categories and 4 tags, `published_at` staggered by 3-day steps so the `latest()`
    ordering shows a believable publish cadence.

## Tests

None added this module (explicitly skipped per user request to move quickly, consistent with Modules
5–8). What was verified: HTTP smoke tests (listing shows only published posts, single post renders content,
draft 404s for guests but 200s for admin preview, category/tag filters narrow results correctly, all admin
CRUD pages load), plus the full existing 75-test suite. If you want test coverage later, natural cases:
`BlogPost::scopePublished()`/`isPublished()` with a future `published_at` (the scheduling behavior is the
one thing that's easy to get subtly wrong), category/tag filter correctness (same shape as Module 3's
`PropertyListingTest`), `relatedPosts()` excluding self and unpublished posts, and the admin
`Form::save()` tag-sync + featured-image-upload paths. **No test files existed for the now-removed
Categories/Tags managers to begin with** (unlike Module 2/3's equivalents), so nothing needed deleting
there. **Still no coverage for**: `quickAddCategory()`/`quickAddTag()`, the card-grid list, or the new tags
dropdown — verified only via `Livewire::test()` scratch scripts during that change.
