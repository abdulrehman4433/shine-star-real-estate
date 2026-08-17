# Module 4: Property Inquiries & Favorites

**Status:** Done. **Acceptance:** inquiry triggers notification; user can favorite/unfavorite and view
saved list. ✅ Verified via 75 automated tests at the time + a route smoke check (no live browser walkthrough
per user's time-boxing request).

**Note:** the doc's "Optional: saved search/alerts" line item was intentionally skipped — it's explicitly
optional and not covered by the acceptance criteria. Add it later if requested.

## What was built

- **`property_inquiries` table:** `property_id` (cascadeOnDelete), `user_id` nullable (`nullOnDelete` —
  guests can submit inquiries, `user_id` is null for them), `name`/`email`/`phone`/`message`, `read_at`
  (nullable, not used yet — reserved for Module 5 CRM read/unread tracking).
- **`favorites` pivot table:** plain `user_id`/`property_id` with a unique constraint on the pair (no extra
  columns) — `withTimestamps()` on the relations.
- **`Property` model additions:** `inquiries()` (hasMany), `favoritedBy()` (belongsToMany User via
  `favorites`), `isFavoritedBy(?User $user)`.
- **`User` model additions:** `favorites()` (belongsToMany Property via `favorites`),
  `toggleFavorite(Property $property): bool` (wraps `$this->favorites()->toggle($id)`, returns whether it
  ended up attached).
- **`App\Notifications\NewPropertyInquiry`** (mail channel only) — sent to the property owner **and every
  user with the `admin` or `super-admin` role** (deduped via `->unique('id')` in case the owner happens to
  also be an admin). Triggered synchronously (not queued) from `InquiryForm::send()` so the sender gets
  immediate feedback; fine at this scale.
- **`Frontend\Properties\InquiryForm`** Livewire component — embedded directly on the property detail page
  (`@livewire('frontend.properties.inquiry-form', ['property' => $property], key(...))`). Prefills
  name/email/phone for logged-in users. Validates `name` required, `email` required+valid, `message`
  required min 10 chars. On success shows an inline "sent" confirmation, doesn't redirect.
- **`Frontend\Properties\FavoriteButton`** Livewire component — small reusable heart toggle
  (`♡ Save` / `♥ Saved`), embedded in both the property card partial (grid/list views) and the detail page.
  Guests clicking it get `$this->redirect(route('login'))` — no favorite is created, no error thrown.
- **`Frontend\Favorites\MyFavorites`** (`/favorites`, full-page Livewire, `['auth','verified']` only — **no
  role restriction**, deliberately: any authenticated user can favorite properties, not just the `user`
  role, since agents/admins browsing listings shouldn't be blocked from using the same heart button
  everyone else sees). Paginated grid, "Remove from Favorites" button per card.
- **Account dashboard** (`Frontend\AccountDashboardController`) now shows live counts:
  `auth()->user()->favorites()->count()` and `PropertyInquiry::where('user_id', ...)->count()`, with a
  "View Favorites" link to `/favorites`.
- **Header nav** (`resources/views/frontend/partials/header.blade.php`) gained a "Favorites" link for any
  authenticated user, next to Dashboard/Profile.

## Gotchas / things to know

- `FavoriteButton` and `InquiryForm` are both `mount(Property $property)` (non-nullable, non-optional) —
  they're always embedded with an explicit property, never used as a standalone route/page.
- Favorites are intentionally NOT restricted by role at the DB/toggle level, even though the "My Favorites"
  *page* lives conceptually under the user dashboard per the spec — see the routing note above.
- If you add saved-search/alerts later: the natural home is a new `saved_searches` table (user_id + filter
  criteria JSON) plus a scheduled command comparing new approved properties against each saved search and
  reusing `NewPropertyInquiry`-style notification delivery.

## Tests

`tests/Feature/Properties/{InquiryFormTest,FavoriteButtonTest}.php`,
`tests/Feature/Favorites/MyFavoritesTest.php` — guest vs authenticated inquiry submission, validation,
notification delivery to owner+admins (and NOT to unrelated users), favorite/unfavorite toggling, mount
reflects existing favorite state, guest redirect-to-login on both favorite and `/favorites` page access,
per-user favorites isolation.
