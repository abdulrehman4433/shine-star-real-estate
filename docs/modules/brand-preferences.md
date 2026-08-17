# Brand Preferences & UI System

Not a numbered module — this is the cross-cutting design system introduced on 2026-07-25 when the admin
panel (and the frontend floating chat widget) were reskinned to the official Shine Star Marketing brand.
Read this before touching **any** admin view or the chat widget: colors, cards, buttons, the sidebar/topbar,
and several UI components all follow the conventions below, and a few of them exist specifically because a
real bug was caught and fixed while building this.

## Brand palette

Defined as CSS custom properties in `resources/css/app.css`, `:root`:

| Token | Hex | Role |
|---|---|---|
| `--ssm-royal-blue` | `#002051` | Primary brand color — buttons, links, active states, icons. Blue-dominant per brand direction. **Changed from `#083F7F` on 2026-08-16 — see the dated note below before touching this value again.** |
| `--ssm-deep-navy` | `#052D64` | Hover/active darker shade of blue; sidebar gradient; gradient endpoints. |
| `--ssm-gold` | `#FBAB03` | **Highlights and CTAs only** — never a full component recolor. Used for: active nav-item accent border, unread dots/badges on the notification bell, contact-icon buttons, the `.btn-gold` utility. |
| `--ssm-gold-light` | `#FDF1D9` | Pale gold tint for hover backgrounds / subtle accents (bell items, contact-icon default state). |
| `--ssm-white` | `#F8FBFC` | Page background (`body { background-color: var(--ssm-white); } `), not pure `#fff`. |

`--ssm-primary` is a back-compat alias for `--ssm-royal-blue` (some earlier component CSS, like the
notification bell, was written against that name before the final official hex codes existed — don't
remove it, other rules still read it).

**Rule: gold is a highlight color, not a base color.** Ordinary buttons, links, and active states stay blue.
If you're about to add `background: var(--ssm-gold)` to something that isn't a CTA, unread indicator, or
explicit highlight, reconsider.

## 2026-08-16: system-wide primary color rebrand (`#083F7F` → `#002051`)

Explicit request: "complete system frontend and backend set button background primary color #002051" — a
single-variable change (`--ssm-royal-blue`) is what makes this a one-line edit instead of a grep-and-replace
across every admin view, since every `.btn-primary`, `.btn-outline-primary`, `.bg-primary`/`.text-primary`,
active nav-pill/tab/pagination-item/dropdown-item/list-group-item, and form-focus ring already reads this one
token rather than a hardcoded hex (that's the whole point of "How the retheme actually works" above).

- **The literal RGB-triple gotcha**: CSS custom properties can't do hex→`rgb()` conversion, so anywhere this
  app needs `rgba(royal-blue, 0.15)` for a shadow/focus-ring, the RGB triple is written out **literally**
  (`rgba(8, 63, 127, .15)`) instead of derived from the hex var — `app.css` had **21 separate literal
  occurrences** of `8, 63, 127` (`--bs-primary-rgb`, `--bs-link-color-rgb`, both `--bs-btn-focus-shadow-rgb`
  declarations, form-control/form-check-input/page-link focus rings, the admin table hover tint, notif-bell
  panel border, contact-button shadows, the auth pages' media-panel gradient overlay, etc.). **All 21 had to
  be replaced too** (`0, 32, 81`, the new color's decomposition) — changing only the hex var and leaving these
  literals alone would have left every focus ring/shadow a mismatched shade of the *old* blue while every
  solid button/badge/link switched to the new navy. **If you ever change `--ssm-royal-blue` again, grep the
  whole file for the *previous* hex's RGB decomposition, not just its own declaration line.**
- **`--ssm-deep-navy` (`#052D64`) was deliberately left unchanged** — it's the hover/active *darker* shade of
  primary, and touching it wasn't requested. Worth knowing: the new primary (`#002051`, RGB sum 113) is now
  actually *darker* than deep-navy (`#052D64`, RGB sum 150) — the opposite of before, when royal-blue (sum 198)
  was clearly lighter than deep-navy. Anywhere both tokens are used together as a light→dark pair (e.g. the
  `.ssm-auth__media-overlay` gradient, the notif-bell badge gradient) the visual step between them is now much
  more subtle than it used to be. Not fixed — re-deriving a proper hover shade for the new primary is a
  separate, not-yet-requested design decision, not a bug.
- **The frontend homepage's own separate `--primary` token** (`Page` row, null slug — see module-07's doc) was
  `#ff5a3c` (orange, from the purchased Resido theme), unrelated to the admin's `--ssm-royal-blue` system
  entirely (it's a different CSS blob, stored in the DB, not `app.css`). Changed to `#002051` too, plus the
  hardcoded `.cta-agent` gradient (`#ff5a3c, #ff8a3c` → `#002051, #043a7a`) — the homepage has **no shared
  stylesheet** with the admin, so this was a second, separate edit via the same "patch the stored `Page.css`
  column" technique module-07 describes for Reviews/CDN fixes, not something the `app.css` change touched.
- **Found and fixed one real pre-existing contrast bug while auditing "wherever background is `#002051`, text
  must be white"**: the header template's fallback text logo (`.resido-logo-text`, only rendered when no logo
  image is uploaded — see module-13) was styled `color: var(--navy)` (`#1c2340`, a *third*, header-template-local
  navy token, distinct from both `--ssm-royal-blue` and the homepage's `--primary`) sitting directly on the
  `.resido-navbar`'s `#002051` background — two very dark, very similar blues, essentially unreadable. Fixed to
  `color: #ffffff`. Everywhere else already using a `#002051` background (the navbar itself, the mobile
  dropdown menu) already had white text from earlier work.
- **This app now has (at least) three independent "primary/brand blue" tokens that don't share a single source
  of truth**: `app.css`'s `--ssm-royal-blue` (admin + any plain-Bootstrap frontend page), the homepage `Page`
  row's own `--primary` (its purchased-theme CSS blob), and the header template's own inline hex values
  (`.resido-navbar`, `.resido-nav .dropdown-menu`, both literally `#002051` rather than a var). They now happen
  to agree after this change, but **there is no mechanism that keeps them in sync** — changing brand color
  again means repeating this same three-places edit, not just bumping one variable.

## How the retheme actually works — read this before changing colors

The project imports Bootstrap's **precompiled** CSS (`@import "bootstrap/dist/css/bootstrap.min.css";` in
`app.css`) — it does **not** compile Bootstrap from SCSS. Switching to SCSS compilation to theme Bootstrap
"properly" was considered and rejected as unnecessarily risky for what was needed.

Instead, the retheme overrides Bootstrap 5.3's own CSS custom properties, in two tiers:

1. **True root-level tokens** Bootstrap reads globally: `--bs-primary`, `--bs-primary-rgb`,
   `--bs-link-color`, `--bs-link-hover-color`, `--bs-body-bg`. Overridden once in a
   `:root, [data-bs-theme="light"] { ... }` block.
2. **Component-scoped tokens** — Bootstrap 5.3 bakes each component variant's actual color as a *local*
   custom property on that component's class (e.g. `.btn-primary { --bs-btn-bg: #0d6efd; ... }`), **not** as
   a reference back to `--bs-primary`. Overriding the root token alone does nothing for these. Every affected
   component is retargeted individually: `.btn-primary`, `.btn-outline-primary`, `.badge.text-bg-primary`,
   `.form-control:focus`/`.form-select:focus`, `.form-check-input:checked`/`:focus`,
   `.form-switch .form-check-input:checked` (gold, not blue), `.nav-pills .nav-link.active`, `.page-link`/
   `.page-item.active .page-link`, `.dropdown-item.active`/`:active`, `.list-group-item.active`,
   `.progress-bar`.

**Net effect: every existing admin page already matches the brand with zero per-page edits**, because they
all just use plain `.btn-primary`/`.card`/`.table`/`.badge`/`.form-control`. If you add a new admin page with
standard Bootstrap components, it inherits the brand automatically — you don't need to hand-color anything.
If you add a **new kind** of Bootstrap component this list doesn't cover yet (e.g. `.btn-check`, `.accordion`,
a spinner variant), check whether it needs the same component-scoped-variable treatment before assuming
`--bs-primary` alone will retint it.

`.btn-gold` is the one component intentionally themed gold (`--bs-btn-bg: var(--ssm-gold)`, navy text for
contrast) — use it for a genuine call-to-action, not as a general button style.

## Global card / table / button polish

Scoped under `#admin-wrapper` (the admin shell's root element) so it never leaks onto the public frontend:

- `.card` — no border, `border-radius: 1rem`, soft shadow (`0 2px 14px rgba(5, 45, 100, .06)`).
- `.card .card-header` — white background, subtle bottom border, rounded top corners matching the card.
- `.table` — hover row tint uses the brand blue at low opacity (`--bs-table-hover-bg`), uppercase small
  letter-spaced header cells (`text-transform: uppercase; font-size: .72rem; letter-spacing: .05em;`).
- `.btn`, `.form-control`, `.form-select` — `border-radius: .6rem` (softer than Bootstrap's default `.375rem`).

## Admin list pages: card grid, not tables (added 2026-08-05)

Properties, Projects, and Blog Posts all list as a responsive grid of cards instead of a `<table>` —
if you build a new admin list page, copy this shape rather than reaching for a table:

```blade
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3">
    @foreach ($items as $item)
        <div class="col">
            <div class="card h-100">
                <div class="card-img-wrap">{{-- image or a placeholder icon --}}</div>
                {{-- badge cluster: position: absolute; top: 8px; left: 8px --}}
                {{-- favorite/featured toggle: position: absolute; top: 6px; right: 6px --}}
                <div class="card-body">{{-- title, 2–3 lines of meta --}}</div>
                <div class="card-footer d-flex justify-content-between">
                    {{-- icon-only buttons (bi-eye/bi-pencil/bi-trash); anything that doesn't fit
                         goes behind a bi-three-dots dropdown, not a wider button group --}}
                </div>
            </div>
        </div>
    @endforeach
</div>
```

1 column on mobile up to 5 on `xl` (≥1200px). Paginate at 20 (`->paginate(20)`), not the older 15.
`Illuminate\Pagination\Paginator::useBootstrapFive()` (registered in `AppServiceProvider::boot()`,
2026-08-05) is required for `{{ $items->links() }}` to render styled pagination at all — without it
Laravel's default **Tailwind** pagination markup renders unstyled, since this project has no Tailwind CSS
loaded.

## Admin shell: sidebar + topbar

- **State lives on `#admin-wrapper`**, not inside the sidebar/topbar partials themselves. In
  `resources/views/admin/layouts/app.blade.php`:
  ```html
  <div class="d-flex ssm-admin-shell" id="admin-wrapper"
      x-data="{ collapsed: ..., mobileOpen: false, toggleCollapse() {...}, toggleMobile() {...} }"
      :class="{ 'is-collapsed': collapsed, 'is-mobile-open': mobileOpen }">
  ```
  `admin/partials/sidebar.blade.php` and `admin/partials/topbar.blade.php` are plain Blade `@include`s, which
  land as literal children in this same DOM node — so they read/mutate `collapsed`/`mobileOpen` directly with
  no props or events needed. **This only works because they're `@include`s, not separate Livewire
  components.** If either one ever becomes its own Livewire component, this cascading-Alpine-scope trick
  breaks and the shared state needs to move to something both can reach (e.g. an Alpine global store).
- **Collapsed (desktop icon-only) state** persists via `localStorage.getItem('ssm-sidebar-collapsed')`.
  Toggled by `.ssm-sidebar__collapse-btn` (only visible `d-none d-lg-inline-flex`).
- **Mobile off-canvas** kicks in under 992px (`@media (max-width: 991.98px)` in `app.css`): the sidebar
  becomes `position: fixed` and slides in via `transform: translateX(...)`, with a `.ssm-sidebar-backdrop`
  (dark navy tint, `d-lg-none`) behind it that closes the drawer on click. Toggled by
  `.ssm-topbar__menu-btn` (`d-lg-none` hamburger).
- **Sidebar nav items** all follow one pattern — copy an existing `<li>` rather than inventing new markup
  when adding a page to the sidebar:
  ```blade
  <a href="{{ route('admin.xxx.index') }}" @click="mobileOpen = false"
      class="ssm-nav__link {{ request()->routeIs('admin.xxx.*') ? 'is-active' : '' }}" title="Xxx">
      <i class="bi bi-whatever"></i>
      <span class="ssm-nav__label">Xxx</span>
  </a>
  ```
  Active state = gold left-border + tinted background (`.ssm-nav__link.is-active`), driven by
  `request()->routeIs(...)`, not a hardcoded per-item flag. The `@click="mobileOpen = false"` on every link is
  required so navigating on mobile actually closes the drawer instead of leaving it open on the next page.
- **CRM submenu** (Leads/Chat) uses Bootstrap's `collapse`/`data-bs-toggle` (not Alpine) for its own
  expand/collapse, nested inside the same sidebar — `.ssm-nav__toggle` + `.ssm-nav__submenu`.
- **CMS submenu** (order, as of 2026-08-05: **Reviews, Pages, Menus, Header, Footer, CDN**) follows the
  exact same collapsible pattern as CRM — a `button.ssm-nav__toggle` (icon: `bi-files`) triggers a
  `div.collapse#cms-submenu` containing the six child links. Active detection uses `$cmsActive` covering
  `admin.reviews.*`, `admin.pages.*`, `admin.menus.*`, `admin.headers.*`, `admin.footer.*`, and
  `admin.cdn.*` routes. The order was explicitly requested (Reviews moved to the front, then later moved to
  just after Footer, above CDN — read the item order literally, it's been deliberately rearranged more than
  once) — don't re-sort it without being asked. If you add another CMS-adjacent admin page, nest it under
  this same `#cms-submenu` collapse rather than adding a new top-level sidebar item.
- **Properties and Projects are plain top-level `<li><a>` links, not collapsible submenus** (as of
  2026-08-05, after being tried both as a shared "Properties" dropdown and as Projects nested inside it —
  see module-02/03/14 docs). Categories/Property Types/Amenities no longer have any sidebar entry at all —
  see the "inline quick-add" convention in the root `README.md`.

## Notification bell

`App\Livewire\Admin\Notifications\Bell` + `resources/views/livewire/admin/notifications/bell.blade.php`. A
**dropdown anchored below the bell icon** (`.notif-bell__panel`, `position: absolute; top: calc(100% + 10px)`)
with an Alpine fade+slide transition — **not a modal**. It was built as a centered modal first and explicitly
changed to a dropdown at the user's request; don't revert this without being asked.

Data source is `Conversation` directly (`whereHas('messages', fn ($q) => $q->whereNull('read_at'))`), not a
persisted notification log — simpler, and matches "show all unread chats" rather than a history of past
notifications. Clicking an item marks it read and dismisses the associated model deep-link expiration.

## The `x-show` + Bootstrap `.modal` trap — read this before adding any Alpine-controlled modal

**A real bug, caught in production use, not just in review:** the CRM Chat contact-details modal
(`resources/views/livewire/admin/chat/manager.blade.php`) was originally built with
`class="modal d-block" x-show="contact"`. Bootstrap's `.d-block` utility is `display: block !important`.
Alpine's `x-show`, when hiding an element, sets a **plain** (non-`!important`) inline `style="display: none"`.
A `!important` class rule always beats a non-`!important` inline style, **regardless of specificity** — so
`d-block` permanently forced the modal visible, and its semi-transparent full-screen backdrop
(`position: fixed; inset: 0;` from Bootstrap's `.modal`) sat on top of the entire page, blurring it and
blocking every click. This shipped and was reported by the user as "the dashboard looks blurry and nothing
is clickable" before it was traced to `/admin/chat` specifically and fixed.

**The fix, and the pattern to always use going forward:** toggle a whole class via `:class`, don't fight
Bootstrap's own component classes with `x-show`:

```blade
{{-- Wrong — d-block always wins over x-show's inline style --}}
<div class="modal d-block" x-show="someFlag">...</div>

{{-- Right — no competing !important rule, Bootstrap's own .modal{display:none} applies when the class isn't present --}}
<div class="modal" :class="{ 'd-block': someFlag }" @click.self="someFlag = null">...</div>
```

Also skip redundant nested `x-show` on `.modal-content`/`.modal-body` inside — the outer wrapper toggling is
sufficient and each extra `x-show` is one more place the same class of bug could resurface.

(Note: this codebase has a **second**, unrelated modal pattern used by e.g. `Admin\Reviews\Manager` (and,
before it was removed 2026-08-05, `Admin\SocialLinks\Manager`) —
`@if ($showModal) <div class="modal d-block">...</div> @endif`, a **Livewire server-side conditional**, not
Alpine. `d-block` is correct and safe there because the element doesn't exist in the DOM at all when
`$showModal` is false — there's no competing inline style to fight. Only the Alpine-`x-show` case above needs
the `:class` treatment.)

## Chat UI components (admin + frontend)

- **Contact-details icon** (`.ssm-contact-btn`) — 38px circle, gold gradient background, white 2px ring
  border, soft shadow, lift-and-brighten on hover. Used in the CRM Chat conversation list (before each row's
  name) and the active-thread header (replacing what used to be a plain-text guest email/phone line — that
  line was removed in favor of this icon). Clicking it sets a local Alpine `contact` object (`{ name, email,
  phone }`, populated server-side per-row via `@js(...)`) which the shared modal at the bottom of
  `manager.blade.php` displays — one modal instance serves every row, not one per row.
- **Reply input** (`.ssm-reply-form` / `.ssm-reply-input` / `.ssm-reply-input__field` / `.ssm-reply-send`) —
  a pill-shaped input with an icon prefix and a circular send button, replacing plain
  `form-control-sm`/`btn-sm`. Used identically in the admin CRM reply box, the frontend `ChatBox` message
  form, and `GuestChatBox`'s message form. Reuse these classes rather than reintroducing plain Bootstrap
  inputs for any new chat-adjacent send box — they're deliberately shared (not admin-scoped) so the same
  input feels identical everywhere someone types a chat message.
- **Message bubbles** (`.ssm-msg-bubble.is-mine` / `.is-theirs`) — rounded-with-one-flat-corner "chat tail"
  bubbles; `is-mine` uses the blue→navy gradient with white text, `is-theirs` uses light gray. Replaces the
  old plain `bg-primary`/`bg-light` + `rounded` classes.
- **Floating widget** (`.ssm-chat-widget`, `.ssm-chat-widget__header`, `.ssm-chat-widget__toggle`) — gradient
  header (matching the sidebar's blue→navy gradient), circular toggle button with a gold ring shadow that
  intensifies on hover, `bi-chat-dots-fill`/`bi-x-lg` icon swap instead of an emoji.
- **Selected conversation row** (`.ssm-chat-list-item.active`, CRM Chat list only) — a **low-opacity brand-blue
  tint** (`rgba(8, 63, 127, .1)` background, `rgba(8, 63, 127, .18)` border), not a solid fill. This is
  deliberately different from Bootstrap's own `.list-group-item.active` (solid `--bs-royal-blue` + white
  text, still the retheme's default elsewhere in the app) — a solid-blue selected row made the row's own
  dark/black text unreadable, so the CRM Chat list gets its own scoped override forcing `color: #1f2937`
  instead of inheriting Bootstrap's white active-text. Scoped to `.ssm-chat-list-item` (a class added
  alongside `.list-group-item`, not a change to the global `.list-group-item.active` rule) specifically so it
  doesn't silently reskin some future unrelated page that starts using `.list-group-item.active` expecting
  the solid-fill default.
- **CRM Chat page (`/admin/chat`) has three stat cards**, not two: Total Conversations, **Unread**
  Conversations (red number), **Read** Conversations (green number, `= total - unread`, computed once in
  `Admin\Chat\Manager::render()` rather than a second query). Keep this three-card shape if you touch that
  page — it was specifically requested after the original two-card version.
- **`/admin/chat` polls every 5s** (`wire:poll.5s="poll"` on `Admin\Chat\Manager`) — this page originally had
  **no live-refresh at all**, unlike every other chat surface in the app (`ChatBox`/`GuestChatBox` at 5s, the
  bell at 10s), which meant new messages/unread counts went stale until the admin manually reloaded or
  closed-and-reopened a thread. `poll()` also re-marks the currently-open conversation read on every tick —
  treating "still has it open while a new message streams in" the same as "just opened it" — otherwise a
  message arriving mid-view would render (thanks to the poll) but stay flagged unread. If you add a new
  admin page that shows live/changing data (counts, lists, unread state), default to giving it the same
  `wire:poll` treatment rather than assuming a Livewire component refreshes on its own — it only refreshes on
  its own actions, never on the passage of time or another user's actions, unless you wire a poll (or a
  dispatched event) for it.

### A `text-truncate` + flexbox gotcha worth remembering

`.text-truncate` (Bootstrap's `overflow: hidden; text-overflow: ellipsis; white-space: nowrap;`) does nothing
useful on a **flex child** unless that child also has `min-width: 0` — flex items default to
`min-width: auto`, which means "never shrink below your content's natural width," directly defeating
truncation (the CRM Chat list's name/property column was stuck at a hardcoded `max-width: 190px` early on
specifically to work around this before the real fix — `class="text-truncate flex-grow-1"` +
`style="min-width: 0;"` — was applied, letting the column use all available space up to the unread badge
instead of a fixed pixel cap).

## Dashboard

`resources/views/admin/dashboard.blade.php` + `App\Http\Controllers\Admin\DashboardController` — stat cards
(`.ssm-stat-card`, icon in a gradient circle, `is-gold` variant for the Leads/Inquiries cards) show **real**
counts (Properties, Leads, Agents, Inquiries), not the original hardcoded zeros. Two `.ssm-chart-card` panels
below render a **pure-CSS bar chart** (`.ssm-bar-row__track`/`__fill`, width set inline per row from a
server-computed percentage) for Properties-by-status and Leads-by-status — deliberately not a JS charting
library, to keep the bundle light. If real interactive charts are wanted later, that's a new dependency
decision to make deliberately, not something to bolt on quietly.

## Naming convention

Every brand-system class is prefixed `ssm-` (Shine Star Marketing) to make it unmistakable which classes are
custom vs. Bootstrap's own. Keep using that prefix for anything new in this system; don't mix in unprefixed
custom class names that could collide with a future Bootstrap release's own utility naming.
