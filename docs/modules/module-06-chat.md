# Module 6: Chat System

**Status:** Done, upgraded five times on 2026-07-25. **Acceptance:** user/agent/guest chat in real-time (or
near-real-time via polling fallback) from the property page; messages persist; the admin side is now
**CRM-Chat-page-only** (no floating widget) with a topbar bell (a dropdown, not a modal) listing every
unread chat, instant mark-as-read on open/reply/bulk, one-click conversion of a chat contact into a CRM
lead, staff can reply directly from `/admin/chat`, and `/admin/chat` itself now polls live (round 5) so new
messages and unread counts show up without a manual reload. Round 5 was live-verified in the browser
(seeded an incoming message via tinker while a conversation was open, confirmed it appeared and counters
updated without reload). Rounds 1–4 were verified via live browser testing (Claude in Chrome) plus the full
75-test suite (round 4's reply-input/bell-sync/modal-removal changes were syntax-checked only, per explicit
user instruction to move fast that round).

## 2026-07-25 upgrade #5: `/admin/chat` live polling

Reported symptom: the topbar bell's counter updated on a new incoming message, but the CRM Chat page itself
(`Admin\Chat\Manager`) didn't — the unread/read stat cards stayed stale, and if a conversation was already
open when a new message arrived, it simply didn't appear until the admin closed and reopened it (or reloaded
the page). Root cause: `Admin\Chat\Manager` had **no `wire:poll` at all** — every other piece of chat UI in
this app polls (`ChatBox` at 5s when a conversation is selected, `GuestChatBox` at 5s, the bell at 10s), but
this component was only ever re-rendering in response to its own user-triggered actions.

- Added `wire:poll.5s="poll"` to the component root in `manager.blade.php`. Because `render()` always
  re-queries everything from scratch, the bare poll alone fixes the list/stat-card staleness.
- Added `Admin\Chat\Manager::poll()`: if a conversation is currently open (`$activeConversationId` set), it
  also calls the existing `markRead()` on every tick — treating "still has it open while a new message
  streams in" the same as "just opened it." Without this, a message arriving while the thread is open would
  render (thanks to the poll) but stay flagged unread until the admin left and came back, undermining the
  fix.
- Added the same auto-scroll-to-bottom pattern the frontend `ChatBox`/`GuestChatBox` already used
  (`x-data x-init="$el.scrollTop = $el.scrollHeight"` + `wire:key` keyed on the message count) to the admin
  thread pane, so a poll-delivered message actually scrolls into view instead of arriving off-screen below
  the fold.

## 2026-07-25 upgrade #4: admin reply input, instant bell counter, bell as modal

- **`Admin\Chat\Manager::sendReply()`** — the CRM Chat detail pane was read-only through upgrade #3
  ("Monitoring view..." footer note); it now has a real reply input. Sends as `sender_id = auth()->id()`
  (the viewing admin/super-admin) and **sets `read_at = now()` on the new message immediately** — if left
  null like a normal incoming message, the bell/unread counters would re-inflate the instant an admin replies
  to their own conversation, since nothing else would ever mark a staff-authored message "read". Broadcasts
  `NewChatMessage` (same event/channels as `ChatBox::send()`) so the real participant sees it live if Reverb
  is running, with the same try/catch resilience if it isn't.
- **Third-party sender labeling in `ChatBox`** (the participant-facing view): since a staff reply's
  `sender_id` is neither the conversation's `initiator_id` nor `recipient_id`, and `ChatBox` only ever
  distinguished "mine" vs. "not mine" (no names), an admin's reply would render visually identical to the
  genuine other participant's messages. Added a small `{{ sender name }} (Support)` label whenever
  `sender_id` isn't one of the two canonical participants, so a real user/agent isn't confused into thinking
  the message came from whoever they were actually chatting with.
- **Instant bell counter sync:** `Admin\Chat\Manager::markRead()` (called from `view()`, `mount()`) now
  `$this->dispatch('chat-updated')` after updating `read_at`. `Admin\Livewire\Notifications\Bell` listens via
  `#[On('chat-updated')]` — Livewire dispatches reach every component already rendered on the same page by
  default, so the bell's badge count updates the moment a conversation is opened/read, without waiting for
  its own poll. The poll itself (`wire:poll`) was tightened from 15s to 10s as the "a brand-new message
  arrived" detection path — there's no push mechanism for that direction (a guest's or another admin's
  browser session can't dispatch a Livewire event into *this* admin's page), so polling is still the only way
  the counter can ever *increase* on its own.
- **Bell is now a centered modal**, not a corner dropdown — same `modal d-block` + `rgba(0,0,0,.5)` backdrop
  pattern already used by `Admin\SocialLinks\Manager`'s modal (that class was removed 2026-08-05 — see
  module-11; `Admin\Reviews\Manager` uses the identical pattern today), swapped in for the old
  `dropdown-menu` positioning. Trigger button and Alpine `open` state are unchanged; only the panel markup
  changed. **Note: per `brand-preferences.md`'s "Notification bell" section, the bell was later changed
  back to a dropdown** — this log entry describes an intermediate state, not what's live today.

## 2026-07-25 upgrade #3: admin-side simplification + lead conversion

## 2026-07-25 upgrade #3: admin-side simplification + lead conversion

Follow-up round, admin-only in scope: (1) remove the floating chat popup from the admin panel entirely —
admins now use the `/admin/chat` (CRM → Chat) page directly, no bubble; (2) rebuild the topbar bell to list
**every unread chat** (not just guest-initiated ones with a notification record) with per-item mark-as-read
+ open, plus "Mark as read" (bulk) and "View all" links; (3) remove the WhatsApp "Continue on WhatsApp"
button and its settings (the user decided against it after using it briefly); (4) add a "Move to Leads"
button on any chat conversation so whoever's on the other end — guest or logged-in user — can be turned into
a CRM lead for later follow-up regardless of whether they ever chat again.

- **`resources/views/admin/layouts/app.blade.php`** no longer includes `@livewire('frontend.chat.chat-widget')`
  at all. The frontend layout (`frontend/layouts/app.blade.php`) is untouched — regular users and guests
  still get the floating widget on the public site; this change is admin-panel-only.
- **`App\Livewire\Admin\Notifications\Bell` was rewritten from notification-log-based to
  conversation-based.** It originally queried `App\Notifications\GuestChatStarted` database-notification
  records (built in upgrade #2); it now queries `Conversation` directly
  (`whereHas('messages', fn ($q) => $q->whereNull('read_at'))`), which is both simpler and matches what the
  user actually asked for ("show all unread chats", not just guest-originated ones with a notification
  record). **`GuestChatStarted` and the `notifyStaff()` call sites in `GuestChatBox` were deleted** — once
  the bell stopped reading them, they were pure dead code (nothing else in the app used the `notifications`
  table). The `notifications` table migration itself was left in place (harmless, standard Laravel
  scaffolding, `User` still has `Notifiable`) in case something wants real notifications again later.
- **Bell UI:** dropdown lists up to 8 unread conversations (name/guest badge, property, last-message
  excerpt, per-conversation unread count, relative time). Clicking one calls
  `goToConversation($id)` — marks every unread message in that conversation read, then redirects to
  `/admin/chat?conversation={id}`. Footer has two links: **"Mark as read"** (`markAllRead()` — a single
  unscoped `Message::whereNull('read_at')->update(...)`, deliberately not limited to the 8 shown, so it
  really means "all", not "all visible") and **"View all"** (a plain `<a href="{{ route('admin.chat.index') }}">`,
  no conversation param — just the CRM list).
- **This intentionally changes the "who can mark what read" stance from upgrade #1's `Admin\Chat\Manager`.**
  That component's own `view()` method still deliberately does *not* mark read (documented reasoning: any
  admin/super-admin can open any conversation via the policy bypass, and marking read on behalf of the real
  recipient could hide a message from them before they've seen it). The **bell's** click-through *does* mark
  read now, because the user explicitly asked for that behavior. These two paths into the same page now
  behave differently on purpose — don't "fix" `Manager::view()` to match the bell without checking this note
  first.
- **WhatsApp removed:** `Admin\Chat\Manager::whatsappUrl()`, the "Continue on WhatsApp" button in
  `manager.blade.php`, and the `whatsappNumber`/`whatsappEnabled` fields + settings-page card in
  `Admin\Settings\GeneralManager` are all gone. The `whatsapp_number`/`whatsapp_enabled` rows were deleted
  from the `settings` table (they were orphaned once nothing read them). If WhatsApp handoff is wanted again
  later, upgrade #2's implementation is the reference (a `wa.me` deep link, no API account needed) — this
  round removed it because the user tried it and asked for it to go.
- **"Move to Leads"** — `Admin\Chat\Manager::moveToLead($conversationId)` creates a `Lead` from either side of
  a conversation (guest fields, or the logged-in initiator's `name`/`email`/`phone`), with `source = 'chat'`
  and a new **`leads.conversation_id`** nullable FK (added this round, `nullOnDelete`) recording where it
  came from. Idempotent: if a lead already exists for that `conversation_id`, the button shows **"View
  Lead"** (linking to the existing `route('leads.show', ...)`, same route agents/admins already use) instead
  of creating a duplicate. `contact` is built as `"email / phone"` when both exist (Lead's schema only has a
  single `contact` string column, following the same convention `PropertyInquiryObserver` already
  established for auto-converting inquiries).
- **`Conversation::initiatorLabel()`** was added to de-duplicate the "guest name + (Guest) badge, or the
  real initiator's name" display logic that both `Admin\Chat\Manager`'s blade and the new bell view needed —
  previously this was an inline `@php` block copy-pasted in the manager view.

## 2026-07-25 upgrade #2: guest chat, notification bell, WhatsApp handoff (superseded — see upgrade #3)

Follow-up request: unauthenticated visitors should be able to chat by giving name/email/phone, admin should
get a notification bell, and there should be a way to hand a conversation off to the business's WhatsApp
number (+923206292107, changeable) when an admin toggle is on. Clarified up front with the user that the
WhatsApp piece would be a **click-to-continue `wa.me` link** (no Twilio/Meta account exists, and none is
needed for this) rather than true automatic forwarding — same "be upfront about what's actually feasible"
approach as the Module 12 SMS stub.

- **Schema:** `conversations.initiator_id` and `messages.sender_id` are now **nullable** (a guest message/
  conversation has no `User` row to point at), and `conversations` gained `guest_name`, `guest_email`,
  `guest_phone` (all nullable strings, populated only when guest-initiated). Migration uses
  `Schema::table(...)->change()`, which required installing `doctrine/dbal` (not previously a dependency) —
  needed so the same migration works portably on both the real MySQL dev DB and the in-memory SQLite the
  test suite uses; a first attempt with raw `ALTER TABLE ... MODIFY` (to dodge the doctrine/dbal dependency)
  passed on MySQL but broke the entire test suite (SQLite doesn't understand `MODIFY`).
- **`Conversation::startGuest(array $guest, User $recipient, ?Property $property = null)`** — the guest
  equivalent of `startBetween()`. **`Conversation::isGuestInitiated()`** and
  **`Conversation::participantLabel(User $viewer)`** were added because `otherParticipant()` assumes both
  sides are real `User` rows and returns `null` (then errors on `->name`) for a guest conversation — every
  view that used to call `otherParticipant($user)->name` directly (`chat-box.blade.php`, the admin
  `Chat\Manager` view) was switched to `participantLabel()`, which falls back to `guest_name` safely.
- **A real, silent bug fixed in the same pass:** `Conversation::unreadCountFor()`, `markReadFor()`, and
  `User::unreadMessagesCount()` all filtered with `where('sender_id', '!=', $user->id)`. In SQL, comparing
  `NULL != x` evaluates to `NULL` (not true), so that condition silently **excluded every guest message from
  unread counts** — a guest's message would never make the badge light up. Fixed by wrapping in
  `where(fn ($q) => $q->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id))` everywhere. Any other
  "not sent by me" query written against `messages.sender_id` needs the same null-aware treatment now that
  the column is nullable.
- **`App\Livewire\Frontend\Chat\GuestChatBox`** — the guest-facing component (form → thread, no list, since
  a guest only ever has one active conversation per context). Two independent instances can exist at once,
  keyed by a **session key that depends on context**: `guest_conversation_property_{id}` when embedded on a
  property page (`$propertyId` prop set), `guest_conversation_general` otherwise. This is deliberate — a
  visitor chatting about one specific property and a visitor using the general "contact us" bubble shouldn't
  collide into the same thread. No Reverb channel for guests (they're not authenticated, can't subscribe to
  a private channel), so it's `wire:poll.5s` only, same fallback mechanism already used elsewhere in this
  module.
- **Two places a guest can start a chat, each with a different recipient:**
  1. **Inline on the property page** (`frontend/properties/show.blade.php`, replacing the old "Log in to
     Chat" link) — `GuestChatBox` mounted with `propertyId`, recipient = `$property->owner`. Authenticated
     non-owners still get the original "Chat with Owner" button/route; this only replaces the **guest**
     branch.
  2. **The floating widget, on any page** (`ChatWidget` — the `@auth`/`@endauth` guard around
     `@livewire('frontend.chat.chat-widget')` in `frontend/layouts/app.blade.php` was removed entirely, and
     the widget now branches internally: authenticated users get `ChatBox` as before, guests get
     `GuestChatBox` with no `propertyId`) — recipient = the first `super-admin` user
     (`User::whereHas('roles', ...)->first()`). There's no per-site "default support agent" setting yet; if
     you want that configurable later, that's the natural extension point (`GuestChatBox::startChat()`).
- **Staff notifications:** `App\Notifications\GuestChatStarted` (database channel only — the user asked
  specifically for a bell, not email) fires on `startChat()` **and** every subsequent guest `send()`. It
  goes to the conversation's actual recipient **and** every `admin`/`super-admin` user, deduplicated — so the
  CRM bell always lights up regardless of who the direct recipient happens to be, consistent with the CRM
  Chat panel's "any admin can monitor any conversation" model from the first upgrade round.
- **Admin notification bell** (`App\Livewire\Admin\Notifications\Bell`, in `admin/partials/topbar.blade.php`)
  — standard Laravel database notifications (`php artisan notifications:table`, `User` already had
  `Notifiable`). Dropdown lists the 10 most recent, `wire:poll.15s` fallback, clicking one marks it read and
  redirects to `/admin/chat?conversation={id}` — which works because `Admin\Chat\Manager::$activeConversationId`
  is now `#[Url(as: 'conversation')]`-bound.
  **A real bug caught via live browser testing, not just reasoning about the code:** the bell's "jump to
  conversation" method was originally named `open()` — but the bell's own Alpine wrapper is
  `x-data="{ open: false }"` (for the dropdown), and Livewire resolves `wire:click` expressions through that
  same Alpine scope. Alpine's `open` boolean **shadowed** the Livewire method of the same name, so
  `wire:click="open('uuid', 3)"` threw `TypeError: open is not a function` in the browser console — clicking
  a notification did nothing, silently, with no server-side error at all (nothing showed in
  `storage/logs/laravel.log`, only in the browser console). Renamed the method to `goToConversation()`.
  **Any Livewire component nested inside (or wrapping) an `x-data` block must avoid method names that
  collide with keys in that Alpine scope** — `open`, `show`, `close`, `active` are the likely collision
  candidates given how this app names its own Alpine toggles.
- **WhatsApp handoff:** `whatsapp_number` (default `+923206292107`) and `whatsapp_enabled` settings, added to
  the existing `Admin\Settings\GeneralManager` page (same card area as the SMS stub). When enabled,
  `Admin\Chat\Manager::whatsappUrl($conversation)` builds a `https://wa.me/{digits-only number}?text=...`
  link (guest name + property + last message, all URL-encoded) shown as a "Continue on WhatsApp" button in
  the CRM Chat thread header. This is **not** automatic forwarding — nothing is sent until a staff member
  clicks the button and hits send in their own WhatsApp. If real automatic forwarding is wanted later, that
  needs a Twilio or Meta Cloud API account (credentials, phone number provisioning, cost) — deliberately out
  of scope until the user actually has one.

## 2026-07-25 upgrade #1: widget UX fixes + CRM Chat admin panel

The user reported the widget "not working" and asked for (1) a properly working frontend chat popup and
(2) a "CRM" admin section with Leads moved under it plus a new advanced Chat panel. Live browser testing
(not just HTTP smoke tests) surfaced two real, confirmed bugs in the original build:

1. **The widget's open/close toggle was a full Livewire server round-trip** (`wire:click="toggle"` on
   `ChatWidget`), which re-queried the whole conversation list just to flip a boolean. It worked, but felt
   broken/laggy — clicking the bubble did nothing for a second or more with no loading indicator, which
   reads as "the button doesn't work" to anyone not staring at network traffic. **Fixed** by moving
   open/close entirely to Alpine local state (`x-data="{ open: false }"`, `x-show`) — `ChatWidget` no longer
   has an `open` property or `toggle()` method at all. The nested `ChatBox` Livewire component still mounts
   on every authenticated page load (now always present, just hidden via `x-show` when closed) — a
   deliberate trade-off: a bit more DB work per page view in exchange for the popup being genuinely instant,
   which matters far more for perceived quality in a small app like this.
2. **The widget's fixed 380px width, split into ChatBox's fixed 260px sidebar + remaining ~120px thread
   pane, was unusably narrow** — message bubbles and names wrapped into an almost single-character-wide
   column. This only showed up once someone actually opened a conversation inside the *widget* specifically
   (the full `/chat` page was always fine, it has the full page width). **Fixed** by adding a
   `ChatBox::$compact` flag (`true` only when nested inside the widget): in compact mode the component shows
   **either** the conversation list **or** the active thread (never both), with a back arrow (`bi-arrow-left`)
   in the thread header to return to the list — a standard mobile-messenger pattern. The full `/chat` page
   still passes no `compact` param and keeps the original two-pane side-by-side layout.

Smaller fixes in the same pass:
- Replaced the raw `<input type="file">` (which rendered the browser's native "No file chosen" text
  truncated/overflowing at a fixed 160px width) with a `bi-paperclip` icon button (a `<label>` wrapping a
  `d-none` file input) plus a small filename chip shown once a file is actually selected.
- Auto-scroll to the bottom on new messages now actually re-fires: the thread's `wire:key` includes the
  message count (`thread-{id}-{count}`), so Alpine re-mounts that element (re-running `x-init`) whenever a
  new message arrives, instead of only scrolling once on the initial open.
- Added a `wire:poll` fallback so chat still feels "live" even if nobody remembered to start
  `php artisan reverb:start`: `ChatWidget` polls every 15s (cheap — just the unread count), and `ChatBox`
  polls every 5s **only while a conversation is actively selected** (`refreshThread()`). Real-time push via
  Reverb is still the primary path when it's running; this is strictly a safety net for local/demo use.

### New: CRM Chat admin panel

- **Sidebar restructure:** "Leads (CRM)" is no longer a flat top-level item. There's now a collapsible
  **"CRM"** parent (Bootstrap `data-bs-toggle="collapse"`, auto-expanded via `request()->routeIs(...)` when
  you're anywhere under it) containing **Leads** and the new **Chat**.
- **`Admin\Chat\Manager`** (`/admin/chat`, `admin.chat.index`) — a monitoring/oversight view, **not** a way
  for admins to inject messages into other people's conversations. Deliberately read-only: the existing
  `conversations` schema is strictly two-party (`initiator_id`/`recipient_id`), and letting admins post into
  arbitrary user↔agent threads would both require a real data-model change (a conversation only tracks two
  participants, there's no room for a silent third party) and be a genuinely creepy support pattern users
  didn't ask for. What it does do:
  - Search conversations by either participant's name/email or the linked property's title.
  - "Unread only" filter toggle.
  - Two stat cards: total conversations, conversations with any unread message.
  - Click a conversation to view the full thread (sender name + timestamp per message, attachment links) —
    same underlying data as the participant-facing views, just through the admin's own eyes.
- **`ConversationPolicy::view()`** now also returns `true` for `hasAnyRole(['admin', 'super-admin'])`, on top
  of the original two-participant check — this is what makes the CRM panel (and, if ever needed, direct
  `/chat/{conversation}` URL access by staff) work without weakening privacy for anyone else.

## What was built (original Module 6 build)

- **`conversations` table:** `property_id` nullable (`nullOnDelete` — optional link per spec),
  `initiator_id`/`recipient_id` (both FK users, `cascadeOnDelete`), `last_message_at` (denormalized for
  cheap sorting of the conversation list without joining messages).
- **`messages` table:** `conversation_id` (cascade), `sender_id` (cascade), `body` (nullable — a message
  can be attachment-only), `read_at` nullable.
- **`Conversation` model:** `otherParticipant($user)`, `unreadCountFor($user)`, `markReadFor($user)`,
  `scopeForUser($userId)`, and the important static helper **`Conversation::startBetween($a, $b, $property = null)`**
  — finds an existing conversation between two users (optionally scoped to a property) regardless of who
  was originally the initiator, or creates a new one. This is what both the seeder and the "Chat with
  Owner" button use — never call `Conversation::create()` directly for starting a chat.
- **`Message` model:** `HasMedia` (attachment, singleFile collection) for optional file attachments.
- **`App\Policies\ConversationPolicy`:** single `view()` rule — only the two participants
  (`initiator_id`/`recipient_id`) can see a conversation. Used both for the `/chat/{conversation}` page
  (`$this->authorize()` in the controller) and the **broadcast channel authorization** (see below).
- **Real-time via Laravel Reverb** (self-hosted, installed via `composer require laravel/reverb` +
  `php artisan reverb:install`, which auto-generated real local credentials in `.env`, added
  `laravel-echo`/`pusher-js` to `package.json`, and created `resources/js/echo.js` — already imported by
  `resources/js/bootstrap.js`, which `app.js` already imports, so no manual wiring was needed there).
  - `App\Events\NewChatMessage implements ShouldBroadcastNow` — broadcasts synchronously (not queued) on
    **three** private channels: `chat.conversation.{id}` (for whoever has that thread open) AND both
    participants' personal `App.Models.User.{id}` channels (so the floating widget's unread badge updates
    even when that conversation isn't open). Payload is minimal (`message_id`) — listeners re-query the DB
    rather than trusting broadcast payload data.
  - `routes/channels.php` (now registered — `reverb:install` added `channels: __DIR__.'/../routes/channels.php'`
    to `bootstrap/app.php`'s `withRouting()`, which wasn't there before): authorizes
    `chat.conversation.{conversationId}` via `$user->can('view', $conversation)`.
- **`App\Livewire\Frontend\Chat\ChatBox`** — the actual chat UI (conversation list + thread + input),
  used in **two places** by the same component class: embedded read the gotcha below on the nullable-property
  bug before touching this file:
  1. Full page at `/chat` and `/chat/{conversation}` — via a thin `Frontend\ChatController@index` +
     `resources/views/frontend/chat/index.blade.php` (NOT a full-page Livewire route — `ChatBox` needs to
     render identically whether it's the whole page or nested in the widget, and Livewire's page-layout
     macros (`->extends()`) only apply to the outermost component of a request, so a controller+view wrapper
     was simpler and safer than fighting that).
  2. Nested inside `App\Livewire\Frontend\Chat\ChatWidget` (the floating button) via `@livewire(...)`.
- **`ChatWidget`** — floating bottom-right button (embedded in both `frontend.layouts.app` and
  `admin.layouts.app`, `@auth`-guarded on the frontend one since admin routes are always authenticated
  anyway), shows a live unread-count badge, toggles open/closed, renders `ChatBox` nested when open.
- **Starting a chat:** "Chat with Owner" button on the property detail page (hidden if you ARE the owner),
  POSTs to `chat.start` (`Frontend\ChatController@start`), which calls `Conversation::startBetween()` and
  redirects straight into `/chat/{conversation}`.
- **Attachments:** file input in the message form, uploaded via `WithFileUploads`, stored on the `Message`
  via medialibrary's `attachment` collection — rendered as a "📎 Attachment" download link in the thread.
- **`ChatSeeder`:** one sample conversation between the demo `user` and the demo `agent` account, about the
  seeded "Modern 2-Bed Apartment" property, with two messages (one read, one unread) so unread badges have
  something to show immediately after seeding.

## Gotchas / things to know (read before touching this module)

- **Two real bugs were caught and fixed during build, both worth knowing about if you touch this code:**
  1. `Message` model used the `InteractsWithMedia` trait but forgot `implements HasMedia` on the class —
     medialibrary's repository does a strict `instanceof HasMedia` check and throws a `TypeError` at
     runtime (not at boot/lint time) the moment any media method is touched. **Any model using
     `InteractsWithMedia` must also `implements HasMedia`** — this is easy to miss since PHP doesn't
     enforce it and everything looks fine until you actually call `getFirstMediaUrl()`/`addMedia()`.
  2. Livewire's `#[On('echo-private:chat.conversation.{conversationId},...')]` dynamic-placeholder
     resolution (`data_get($component, 'conversationId')`) treats a **null** property value as "the key
     doesn't exist" and throws `Unable to evaluate dynamic event name placeholder`. This broke `ChatBox`
     every time nothing was selected yet (fresh widget open, or `/chat` with no conversation param). Fixed
     by making `conversationId` a non-nullable `int` defaulting to `0` instead of `?int = null` — `0` isn't
     a real conversation id so the resulting bogus channel name is harmless, and all the existing
     `if ($this->conversationId)` truthy-checks still work correctly since `0` is falsy in PHP.
     **Any future `#[On(...)]` dynamic placeholder in this app must reference a property that is never
     null** — use a sentinel default instead.
- **Broadcast failures are caught on purpose.** `ChatBox::send()` wraps the `broadcast(...)->toOthers()`
  call in try/catch — the message is already persisted to the DB before that call runs, so if the Reverb
  server isn't running (very likely during casual local testing), the user still successfully sends their
  message; they just don't get the real-time push to the other participant until they reload. Confirmed
  via tinker that `broadcast()` really does throw `Illuminate\Broadcasting\BroadcastException` when Reverb
  is down, and that the message row still exists afterward.
- **For real-time to actually work you must run the Reverb server**: `php artisan reverb:start` (separate
  terminal, alongside `php artisan serve`). Nothing auto-starts it. In production this would run under a
  process supervisor (systemd/supervisor), not manually.
- `ShouldBroadcastNow` (not `ShouldBroadcast`) was chosen deliberately so chat messages don't depend on a
  queue worker running (`QUEUE_CONNECTION=database` has no guaranteed worker in this dev setup, same
  reasoning as the Module 4 inquiry notification).
- The unread-badge-updates-without-opening-the-conversation behavior only works because `NewChatMessage`
  also broadcasts on both participants' `App.Models.User.{id}` channels, which `ChatWidget` listens to.
  If you ever remove those personal-channel broadcasts, the floating widget's badge will go stale until
  the next full page load.
- **(2026-07-25) `ChatBox`'s `open`/`toggle` no longer exist on the Livewire side** — the widget's
  open/close is pure Alpine state now (`x-data="{ open: false }"` in `chat-widget.blade.php`). If you add
  something that needs to know whether the widget is currently open from PHP (e.g. to skip a query when
  closed), you'll need to either add that state back deliberately or find another signal — don't assume a
  server-side `$open` property still exists.
- **(2026-07-25) `ChatBox::$compact`** controls the single-pane-with-back-button vs. two-pane-side-by-side
  layout. Always pass `['compact' => true]` when nesting `ChatBox` anywhere narrower than roughly 500px
  (the original widget bug was exactly this: a 380px container trying to render a fixed 260px sidebar +
  thread pane side by side, leaving almost nothing readable for the thread). The full `/chat` page passes
  no `compact` param (defaults `false`) and keeps both panes visible.
- **(2026-07-25, upgrade #2) `sender_id`/`initiator_id` are nullable now — any new query filtering on
  "messages not from me" or "the other participant" must be null-aware.** `!=` comparisons against a
  nullable column silently drop NULL rows in SQL instead of matching them; use
  `where(fn ($q) => $q->whereNull(...)->orWhere(...))`. This bit `unreadCountFor()`/`markReadFor()`/
  `unreadMessagesCount()` for real (guest messages never counted as unread until fixed) — treat it as a
  standing rule for this table, not a one-off fix.
- **(2026-07-25, upgrade #2) Don't name a Livewire component method the same as a key in a wrapping
  `x-data` scope** (`open`, `show`, `close`, etc.). Livewire resolves `wire:click="methodName(...)"`
  through the same Alpine expression evaluator as the surrounding `x-data`, so a same-named Alpine property
  silently shadows the Livewire method — no server error, just a browser-console `TypeError` and a dead
  click. Caught in `Admin\Notifications\Bell` (`open()` vs. its own `x-data="{ open: false }"`); renamed to
  `goToConversation()`.
- **(2026-07-25, upgrade #2) `GuestChatBox`'s session key depends on context**
  (`guest_conversation_property_{id}` vs. `guest_conversation_general`) — don't collapse these to one key,
  or a guest chatting about Property A and then opening the general widget bubble would incorrectly resume
  the Property A conversation instead of starting a fresh general one.

## 2026-08-16: unread badge on the floating widget icon for guests too

The floating `ChatWidget` icon already had an unread-count badge (`bg-danger` pill, top-right of the toggle
button, refreshed by its own `wire:poll.15s`) — but it was hardcoded to `0` for guests
(`auth()->check() ? auth()->user()->unreadMessagesCount() : 0`), so a guest who closed the widget after a
staff reply had no way to know a reply was waiting without reopening it manually. Fixed without adding any
new schema — `messages.read_at` and the null-`sender_id`-means-guest convention already existed, just weren't
being used from the guest side of this particular flow:

- **`Conversation::unreadCountForGuest()`/`markReadForGuest()`** — the guest-side equivalents of the existing
  `unreadCountFor(User)`/`markReadFor(User)`. Simpler than the `User` versions: a guest's own messages always
  have `sender_id === null`, so "unread for the guest" is just "any staff-sent (`sender_id` not null) message
  with `read_at` still null" — no need to exclude the viewer's own id.
- **`Conversation::unreadCountForGuestSession()` (static)** — the guest equivalent of
  `User::unreadMessagesCount()`. A guest can have more than one active conversation at once (see
  `GuestChatBox::sessionKey()` — one `guest_conversation_general` plus one `guest_conversation_property_{id}`
  per property page they've chatted from), so this sums unread staff messages across **every**
  `guest_conversation_*` key currently in the session, found via `Conversation::guestSessionConversationIds()`
  filtering `session()->all()` by key prefix rather than needing to know property IDs in advance.
- **`ChatWidget::render()`** now calls `Conversation::unreadCountForGuestSession()` for the guest branch. No
  other change needed for the "another tab" requirement — the widget already polls every 15s and PHP sessions
  are shared across all tabs of the same browser via the session cookie, so a second tab's own widget instance
  picks up a new reply within its own next poll tick automatically.
- **Marking read on open — the one genuinely tricky part.** `GuestChatBox` is *always mounted* on every page
  (it's rendered inside the widget's collapsible panel, but that panel's open/closed state is purely a client-side
  Alpine `x-data`/`x-show` toggle in the *parent* `ChatWidget`'s view — the child Livewire component itself is
  never destroyed/remounted when the panel closes). That ruled out the two obvious places to mark messages
  read: `mount()` runs once per page load regardless of whether the panel is ever opened, and the existing
  `wire:poll.5s="refreshThread"` (conditional on `$step === 'thread'`, not on visibility) runs continuously in
  the background whether the panel is open or closed — marking read in either of those would clear the badge
  before the guest had actually seen anything. **Fix:** the toggle button's `@click` handler
  (`chat-widget.blade.php`) now does `open = !open; if (open) { $wire.dispatch('chat-widget-opened') }` —
  only fires the instant the panel transitions closed→open, and only in that direction (closing doesn't
  re-fire it). `GuestChatBox` listens via `#[On('chat-widget-opened')]` → `markAsRead()` →
  `Conversation::find($this->conversationId)?->markReadForGuest()`. Dispatching from `$wire` (which in this
  Alpine scope refers to the *parent* `ChatWidget` instance, since that's the enclosing Livewire component for
  this blade file) also forces `ChatWidget` itself through a normal request/render cycle, which is what makes
  the badge disappear immediately on open rather than waiting up to 15s for the next poll — no separate
  "tell the parent to refresh" event was needed.
- **Deliberately not handled**: a new staff reply arriving *while the panel is already open* doesn't
  auto-mark-read the instant it arrives — it's only marked read on the next closed→open transition. Not asked
  for, and doing it properly would mean continuously syncing Alpine's `open` boolean to the server (e.g. via
  `$wire.entangle`) just to gate the existing 5s poll, which is meaningfully more plumbing for a case the
  request didn't describe (the described flow is specifically close → staff replies → badge appears → guest
  reopens → badge clears).
- **Verified via a scratch `Livewire::test()` script** (not committed, per this module's existing testing
  convention): seeded a guest conversation + staff reply, confirmed
  `Conversation::unreadCountForGuestSession()` was 0 before the reply and 1 after, confirmed the widget
  actually renders that count, called `GuestChatBox::markAsRead()` directly (simulating the dispatched event),
  and confirmed the count dropped back to 0.

## 2026-08-16: conversation "close" lifecycle, email-based guest resume, and a ring animation on the widget icon

Three related requests bundled together — a conversation now has a real open/closed lifecycle, which is
what makes the other two behaviors ("same email starts fresh after closing" and "resumes the old thread
otherwise") actually well-defined rather than ambiguous.

- **`conversations.closed_at`** (nullable timestamp, additive migration) is the only new schema. `isClosed()`
  is just `! is_null($this->closed_at)`; `close()` sets it to `now()`. **Closing never deletes anything** —
  a closed conversation stays fully visible in the admin manager and the user's own chat list (with a
  "Closed" badge), the only behavioral effect is that it's never resumed by the two "find existing" methods
  below. There's deliberately no `reopen()` — once closed, the only way forward is a new conversation.
- **`Conversation::startBetween()`** (authenticated user ↔ user) now scopes its existing-conversation lookup
  with `->openOnly()` — previously it would resume ANY prior conversation between the two parties regardless
  of state; now a closed one is skipped and a fresh conversation is created instead.
- **`Conversation::findOrStartGuest()`** (new) is the guest equivalent, and the more interesting half: it
  matches an existing **open** conversation by `recipient_id` + `property_id` + **`guest_email`** —
  deliberately independent of the browser session. Previously `GuestChatBox`'s only "is this the same
  conversation" signal was the session-stored conversation id (`guest_conversation_*` keys) — meaning a guest
  emailing in from a new tab, a different device, or after clearing cookies would always get a brand-new,
  disconnected conversation even if they were mid-conversation with staff. Now: same email + still-open
  conversation → resumes the same thread (updating `guest_name`/`guest_phone` in case they changed) regardless
  of session state; same email + the prior conversation is closed → starts a genuinely new one. This is what
  "admin can view his old chat where he left off" vs. "same email, new chat" actually means: which one happens
  depends entirely on whether that prior conversation was closed. `startGuest()` (the unconditional "always
  create" primitive) still exists underneath, kept separate so both intents stay distinguishable at the call
  site — `findOrStartGuest()` is what `GuestChatBox::startChat()` actually calls now.
- **"Close Chat" added on all three surfaces** — `GuestChatBox`, `ChatBox` (authenticated), and
  `Admin\Chat\Manager` — each with its own `closeChat()`/`closeConversation()` method (kept a distinct name
  from `Admin\Chat\Manager::closeThread()`, which already existed and means something different: deselecting
  the currently-viewed thread panel in the admin UI, not closing the conversation itself — reusing that name
  for the new feature would have collided with existing, unrelated behavior). All three `send()`/`sendReply()`
  methods now guard `if ($conversation->isClosed()) return;` (with a `notifyWarning()` on the admin side) so a
  stale open tab/session can't still post into a conversation that's since been closed elsewhere. The guest
  side's close also forgets the session key and resets straight back to the start-chat form (`step = 'form'`)
  — ready for a new conversation immediately, matching the "new chat" half of the behavior above. All three
  views show a "Closed" badge (list item + thread header) and swap the reply form for a plain "this
  conversation is closed" message once `isClosed()` is true.
- **Ring animation on the floating widget icon** — a separate, purely cosmetic addition: `ChatWidget` now
  tracks `$lastSeenUnread` (a public property, persisted across requests via Livewire's normal snapshotting)
  and compares it against the freshly-computed count on every `render()` (which fires on the guest's 15s poll,
  or immediately via the existing Reverb echo listener for authenticated users). On a genuine **increase**
  (never on a decrease, e.g. right after the guest opens the panel and it's marked read — that would otherwise
  ring on every single poll forever) it dispatches a `chat-unread-increased` browser event, which
  `chat-widget.blade.php`'s toggle button picks up via a small `x-data="{ ring: false }"` +
  `x-on:chat-unread-increased.window` listener to add a `.ssm-chat-widget__toggle--ring` class for 1s (a CSS
  `@keyframes` rotation wiggle in `app.css`). **Why an event instead of having Alpine watch the count
  directly**: `wire:poll` morphs the existing DOM node rather than re-creating it, so an `x-data="{ count:
  {{ $unreadCount }} }"` value baked into the initial HTML never gets refreshed by later polls — Alpine has no
  way to "notice" a plain server-rendered number changed on morph. Dispatching an explicit event from the
  server every time the underlying condition (an increase happened) is true sidesteps that entirely, and is
  the same general technique already used elsewhere in this app for cross-component Livewire/Alpine signaling
  (see the 2026-08-16 guest-unread-badge entry above's `chat-widget-opened` event).
- **Verified via scratch scripts** (tinker + a throwaway `Livewire::test()`, not committed, per this module's
  usual convention): same-email guest resume when open (1 conversation across two "sessions"), same-email
  guest NOT resumed once closed (2nd conversation created), `startBetween()`'s equivalent for two real users,
  and the ring event firing exactly once when a staff reply pushes the count from 0 to 1.

## 2026-08-16: real-time push for guests too (public per-conversation channel)

Follow-up to the badge/ring work above — the guest side was still poll-only (5s thread poll, 15s widget
poll), which is "notified eventually" rather than "notified promptly." Guests structurally can't use this
app's existing private channels: `Broadcast::channel('App.Models.User.{id}', ...)` and the
`chat.conversation.{id}` private channel's authorizer both require an authenticated user (`/broadcasting/auth`
has nothing to authenticate for an anonymous visitor), and a guest-initiated conversation's `initiator_id` is
literally `null` — `new PrivateChannel("App.Models.User.{$conversation->initiator_id}")` would have produced
a degenerate `private-App.Models.User.` channel name that nothing could ever subscribe to.

- **`NewChatMessage::broadcastOn()`** now branches: authenticated-to-authenticated conversations broadcast
  exactly as before (two private user channels + the private per-conversation channel, used by the admin
  panel). For a guest-initiated conversation (`initiator_id` is null), the private "initiator" channel is
  replaced with a **public** `Channel` named `chat.conversation.{id}.public` instead. Public channels need no
  `Broadcast::channel()` authorizer at all — Reverb allows anyone to subscribe. The payload
  (`broadcastWith()`) was already minimal before this change (`['message_id' => ...]`, no body/name/email),
  so a public listener can only ever learn "conversation N got a new message," never its content — the actual
  message text is still fetched through a normal authenticated/session-scoped Livewire render, not the
  broadcast payload. **Trade-off worth knowing**: conversation IDs are plain sequential integers, so this is
  technically enumerable (anyone could subscribe to every `chat.conversation.N.public` and learn traffic
  timing across all guest conversations site-wide) — accepted as low-risk for this app given the payload
  carries no content, but if that ever needs hardening, swap the public channel name for something
  non-sequential (a UUID column, or a signed/hashed id) rather than the raw autoincrement key.
- **`GuestChatBox::messageReceivedLive()`** — `#[On('echo:chat.conversation.{conversationId}.public,message.sent')]`
  (note the `echo:` prefix, not `echo-private:` — that's what tells Livewire's JS runtime to use
  `Echo.channel()` instead of `Echo.private()`). Re-renders the thread (new message shows immediately,
  whether the panel is open or minimized) and dispatches a plain `chat-message-received` browser event.
- **`ChatWidget::refreshOnGuestMessage()`** listens for that dispatched event (same cross-component
  Livewire-event technique as `chat-widget-opened` from the previous entry, just bubbling the other
  direction — child to parent instead of parent to child) so the floating icon's badge count and ring
  animation update instantly on a live guest reply too, instead of waiting for the widget's own 15s poll.
  Authenticated users didn't need this wiring — their `ChatWidget` already has its own direct
  `App.Models.User.{userId}` private channel subscription that fires independently of any child component.
- **Reverb must actually be running** (`php artisan reverb:start`) for any of this — nothing in this app
  auto-starts it (see root README's existing note on this). Without it, every `broadcast(...)` call already
  degrades gracefully to "message still saved, live push just doesn't happen" via the try/catch every
  `send()`/`sendReply()` wraps it in — the 5s/15s polls remain as the permanent fallback, not just a
  temporary one until Reverb is configured.
- **Verified**: constructed a guest-initiated `NewChatMessage` and an authenticated-to-authenticated one via
  tinker and printed `broadcastOn()`'s channel list for each — confirmed the guest one gets exactly
  `private-chat.conversation.N`, `private-App.Models.User.{recipient}`, and `chat.conversation.N.public` (no
  private initiator channel), while the authenticated one gets the original three-private-channel shape
  unchanged. Also confirmed a real `broadcast(...)->toOthers()` call succeeds without error once
  `reverb:start` is running (it previously logged a warning and fell back silently, per the try/catch, when
  Reverb wasn't up).

## 2026-08-16: real bug — authenticated users' badge silently cleared itself while minimized

Reported directly: "counter still doesn't show if a user minimizes the chat." The guest side (previous entry)
was correct; **`ChatBox`, the authenticated-user component embedded in the widget, was not** — this was a
real, pre-existing bug that the new "show unread while minimized" requirement simply exposed for the first
time (nobody previously cared whether the widget silently marked things read in the background).

`ChatBox` is reused in two places: the full `/chat` page (`compact=false`, no minimize concept — the page
being open at all means the user is looking at it) and, unindented, embedded inside the floating widget
(`compact=true`, `key('chat-widget-box')` in chat-widget.blade.php) where it stays **mounted and polling in
the background at all times**, regardless of whether the parent widget's Alpine `x-show="open"` is currently
hiding it. `mount()`, `selectConversation()`, `messageReceived()` (the real-time echo listener), and
`refreshThread()` (the 5s poll fallback) **all unconditionally called `markReadFor(auth()->user())`
whenever `$conversationId` was set** — with no awareness of whether the panel was actually visible. Sequence:
user minimizes → admin replies → within 5s the background `refreshThread()` poll (or the real-time echo,
whichever fires first) marks it read automatically → `ChatWidget`'s own badge count (queried fresh from the
DB) is already 0 by the time anyone looks at it. The guest side never had this problem because
`GuestChatBox::refreshThread()` was already an intentionally empty no-op from the start (see the very first
guest-badge entry above) — this fix brings `ChatBox` in line with that same principle for the first time.

- **New `ChatBox::$isOpen` property** — `true` on the full page (`mount()` sets `$isOpen = ! $compact`, so
  the full-page case is unaffected and keeps marking read immediately, exactly as before), `false` by default
  in widget mode (matching Alpine's own `open: false` initial state).
- **`messageReceived()`/`refreshThread()`** now gate the `markReadFor()` call on `$this->isOpen` — they still
  re-render either way (any `#[On]` hit or poll tick is a normal Livewire round-trip, which is what keeps the
  thread content and the widget's badge number fresh even while minimized), just without the side effect of
  marking anything read until the panel is genuinely open.
- **`chat-widget.blade.php`'s toggle button** now dispatches `chat-widget-opened` **or** `chat-widget-closed`
  depending on the new `open` state (previously only ever dispatched on opening) — `ChatBox` listens for both
  via `#[On]` to flip `$isOpen`. **The in-panel header's own "×" close button was a second, separate bug in
  the same family**: it only did `open = false` with no dispatch at all, so closing via that button (instead
  of the main toggle) would have left `$isOpen` stuck at `true` server-side even though the panel was visually
  closed — fixed to dispatch `chat-widget-closed` too.
- **`selectConversation()` was deliberately left ungated** — clicking a conversation in the (necessarily
  visible, since you're clicking it) list is always a real "look at this now" action in both compact and
  full-page mode, so it keeps marking read unconditionally regardless of `$isOpen`.
- **Verified via a scratch `Livewire::test()`**: selected a conversation in compact mode, called `panelClosed()`,
  had the "admin" create a reply, called `refreshThread()` (simulating the background poll firing while
  minimized) and confirmed the message was still unread afterward and the widget's badge rendered "1"; then
  called `panelOpened()` and confirmed it dropped to 0. Separately confirmed the full `/chat` page's mount
  behavior is unchanged (`isOpen` is `true`, unread count is `0` immediately after mounting with a
  conversation) — no regression there.

## Tests

None added this module (explicitly skipped per user request to move quickly, same as Module 5). What was
verified originally: HTTP smoke tests (chat pages return 200 for participants, 403 for non-participants,
seeded message content actually renders), and a tinker script confirming the broadcast-try/catch resilience
path. Upgrade #1 added live browser verification (Claude in Chrome) of the widget toggle, compact-mode
layout switching, sending a message end-to-end, and the admin CRM Chat panel's search/filter/policy-bypass
behavior. Upgrade #2 added live browser verification of: the inline property-page guest form → thread
transition, guest session persistence across a page reload, staff notification fan-out (recipient +
all admins, verified via tinker against the real dev DB), the notification bell dropdown and its
click-through to a preselected conversation (`?conversation=`), and the WhatsApp button's generated `wa.me`
URL. Still no automated tests. If you want test coverage later, the natural cases mirror other modules:
`ConversationPolicy` scoping (participant vs. non-participant, guest redirect, and now the admin/super-admin
bypass), `ChatBox::$compact` rendering the right pane for a given state, `Admin\Chat\Manager`'s search/
unread-only filters, `Conversation::startBetween()`/`startGuest()` idempotency, `ChatBox::send()` persisting
a message + marking `last_message_at`, `Event::fake()` assertions that `NewChatMessage` is dispatched on the
right three channels, `GuestChatBox::startChat()` picking the right recipient (property owner vs. default
super-admin), and `unreadCountFor()`/`markReadFor()` correctly counting a null-`sender_id` guest message as
unread. Upgrade #3 added live browser verification of: the admin panel having no floating widget, the bell
listing real unread conversations (seeded via tinker) with correct excerpts/counts, clicking a bell item
marking it read and landing on `/admin/chat?conversation=X` with the badge count decremented, "Mark as read"
clearing every unread conversation in one click, "View all" linking to plain `/admin/chat`, and the full
"Move to Leads" → "View Lead" round trip (lead created with the right name/contact/source, idempotent
button swap, and the resulting `/leads/{id}` page rendering correctly). If you want automated coverage for
this round specifically: `Bell::goToConversation()`/`markAllRead()` marking the right messages read (and
only those), `Admin\Chat\Manager::moveToLead()` idempotency (second click doesn't create a duplicate lead),
and `Conversation::initiatorLabel()` for both guest and user-initiated conversations.
