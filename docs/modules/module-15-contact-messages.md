# Module 15: Contact Messages, Dynamic SMTP, and the /contact-us Page

**Status:** Done (2026-08-17). **Acceptance:** the site's contact form actually submits and persists,
every submission shows up under CRM → Contact Messages in the admin panel, a configurable recipient gets
emailed about new submissions, and outgoing email can be routed through admin-configured SMTP credentials
stored in the database instead of only the server's `.env` file.

## Two separate contact forms — this app has both, and they needed two different fixes

There are genuinely two different "contact form" surfaces in this codebase, rendered through two different
mechanisms, and this module had to fix both:

1. **`App\Livewire\Frontend\Pages\ContactFormBlock`** — a real Livewire component, used as a `contact-form`
   block type (`App\Enums\PageBlockType`) inside the Page Builder's block system (Module 7). This already
   existed and was already wired to `wire:submit`, but didn't persist anything to the database — it only
   fired a `mail`-channel Notification to every Admin/SuperAdmin user, then set a `sent` flag. No record of
   who contacted the site (or what they said) survived past that one email.
2. **The actual live `/contact-us` page** — turned out to **already exist** as a `Page` row with its own
   hand-authored, custom `html`/`css` (same "stored raw template" mechanism the homepage uses, see
   module-07's doc) — a fully custom-designed page (hero, a form + "Get In Touch" info panel, a CTA
   banner) that was **not** using the Page Builder's block system at all. Its form was **plain static
   HTML** — no `wire:submit`, no `action`/`method`, no `name` attributes on the inputs, a `<button
   type="submit">` that submitted nowhere. **This is the one the request was actually about** — the first
   real Contact page a visitor would find already existed and looked finished, but silently did nothing
   when submitted.
   - **A real seeder mistake happened here, caught and corrected in the same session**: a new
     `ContactPageSeeder` was written assuming no `contact-us` page existed yet (following `PageSeeder`'s
     `firstOrCreate`-by-slug + block-based pattern for `about-us`). Since the slug already existed,
     `firstOrCreate` found the existing row and — because it happened to have zero `page_blocks` rows yet
     despite having a stored `html` template — the seeder's "skip if it already has blocks" guard didn't
     trigger, and two new orphan blocks got created that had **zero effect** on the live page (the
     custom-`html` render path in `PageController::show()` takes priority over the block-based path
     whenever `html` is non-empty, so the blocks were just inert rows). Caught immediately by checking
     `$page->blocks()->count()` before assuming the seeder had done anything useful, and deleted those two
     orphan rows. **The seeder is still registered** in `DatabaseSeeder` — it's correct and valuable for a
     genuinely fresh `migrate:fresh --seed` install (where no `contact-us` page exists at all yet), it just
     turned out to be a no-op against this particular already-populated dev database. **Lesson: on a
     `firstOrCreate`-by-slug seeder, "the row already existed" and "the row already has the content this
     seeder would add" are two different conditions — checking only the row's existence isn't enough to
     conclude the seeder had no effect.**

## Schema

- **`contact_messages`** — `name`, `email`, `phone` (nullable), `subject` (nullable), `message`,
  `page_title` (nullable — which page/context it came from), `read_at` (nullable timestamp, unread = null),
  timestamps.
- **`App\Models\ContactMessage`** — plain model, `scopeUnread()`, `isRead()`/`markRead()`/`markUnread()`.

## The plain-HTML form's real constraint: no Blade, no Livewire, inside stored content

Page HTML with a non-empty `html` column is rendered via `Page::renderHtml()` → `safeSubstitute()` — the
exact same restricted, non-Blade-compiling substitution mechanism `HeaderTemplate`/`PageTemplate` already
use (see module-13's doc for the full security rationale: running arbitrary stored HTML through
`Blade::render()` would be RCE). It only recognizes `{{ $var }}` (escaped) and `{!! $var !!}` (raw)
referencing a **bare variable name** — no `@if`, no `@livewire(...)`, no function calls. This ruled out
embedding the Livewire `ContactFormBlock` directly into this page (Blade directives aren't processed at
all) and ruled out any conditional "show success message" logic living in the stored HTML itself. The fix
had to be a **plain, non-Livewire POST form**, with all conditional logic computed in PHP beforehand and
handed over as pre-built variables/HTML strings:

- **`App\Http\Controllers\Frontend\ContactController::submit()`** (new) — a plain `Route::post('/contact-us', ...)`
  endpoint. Validates, creates a `ContactMessage`, calls the shared notifier (below), and redirects back
  with `session('contact_success', true)`.
- **`PageController::show()`** now always adds four extra keys to `$pageData` for any custom-`html` page
  (harmless, unreferenced placeholders on every page that isn't `/contact-us`): `csrfToken` (`csrf_token()`),
  `contactStatusHtml` (a pre-built `<div class="alert...">` string — success message, or a `<ul>` of
  validation errors, or an empty string — computed by a new private `contactStatusHtml()` method reading
  `session('contact_success')`/`session('errors')`), and `oldName`/`oldEmail`/`oldMessage` (`old('name', '')`
  etc., HTML-escaped) so a validation failure doesn't wipe out what the visitor already typed.
- **The stored Page HTML itself was edited** (a scratch `tinker` script, same technique as every other
  stored-content edit this session — see root README's "editing a homepage/header-template block" gotcha)
  to add `action="/contact-us" method="POST"`, a hidden `_token` field reading `{{ $csrfToken }}`, `name`
  attributes on the three inputs, `value="{{ $oldName }}"`/`{{ $oldEmail }}`/text-content `{{ $oldMessage }}`
  pre-fills, and `{!! $contactStatusHtml !!}` printed just above the `<form>`. Nothing about the page's
  actual visual design (hero, "Get In Touch" info column with real phone/email/address, CTA banner) was
  touched — only the form itself.
- **Verified with a real HTTP round-trip** (not just `Livewire::test()`, since there's no Livewire component
  involved here at all): fetched the live page with `curl`, extracted the real CSRF token and session
  cookie from the response, POSTed a genuine submission, followed the redirect, and confirmed both the
  success message rendered and a `ContactMessage` row was created with the right data. (First attempt hit a
  stray 419 CSRF mismatch from accidentally reusing a stale cookie jar file left over from unrelated testing
  much earlier in the same session — not a bug in the feature, a test-hygiene mistake; a fresh cookie jar
  worked immediately.)

## `App\Services\ContactNotifier` — one notify path shared by both forms

Both `ContactFormBlock::send()` and `ContactController::submit()` call the same
`ContactNotifier::notify(ContactMessage $message)` static method, so "who gets emailed and under what
conditions" can't drift between the two entry points. It:

- Does nothing at all if the `contact_notify_enabled` setting is off.
- Sends to `contact_notify_email` if set, otherwise falls back to the existing general `contact_email`
  setting, otherwise does nothing (no recipient configured at all).
- Uses `Notification::route('mail', $recipient)->notify(new NewContactMessage($message))` — an **on-demand
  notification** (Laravel's term for notifying a plain email address with no `User`/`Notifiable` model
  behind it), since the recipient here is just a configured address, not a user account. This is a
  **first-time pattern in this codebase** — every other mail notification (`LeadAssigned`,
  `NewPropertyInquiry`, etc.) notifies an actual `User` model.
- Wrapped in try/catch, logging a warning rather than throwing — same resilience principle already used
  around chat's `broadcast()` calls: **a down/misconfigured mail server must never prevent the message
  itself from being saved.** The `ContactMessage` row is the system of record; the email is just an alert.
- **`App\Notifications\NewContactMessage`** was refactored to take a `ContactMessage $contactMessage`
  instead of four loose scalar constructor args (name/email/message/pageTitle) — cleaner, and lets the
  mail body include phone/subject when present plus an "View in Admin Panel" action button linking to
  `route('admin.contact-messages.index', ['contact' => $id])`. Confirmed no other call site existed before
  changing the signature (this notification was only ever used from the one Livewire component).

## `Admin\ContactMessages\Manager` — new CRM page

Mirrors `Admin\Chat\Manager`'s list+detail split-pane shape (search, unread-only filter, 3 stat cards,
`#[Url(as: 'contact')]`-bound active-item id so deep links work) rather than `Admin\Leads\Manager`'s simpler
flat-list shape, since contact messages need the same "mark read on view" concept chat conversations have —
Leads have no such concept at all.

- List (left, `col-md-5`): search by name/email/subject, "Unread only" toggle, `list-group` of messages
  with a red "New" pill badge for unread ones.
- Detail (right, `col-md-7`): full message body, a **"Mark Unread"** button (only shown once read — there's
  no "mark read" button since viewing a message already does that, same as chat), a **delete** button
  (`wire:confirm`-gated), and a **reply box** that sends a real email straight back to the submitter via
  `Mail::raw()` — using whatever mailer is currently configured (the server's `.env`, or the admin-configured
  dynamic SMTP settings below, whichever `AppServiceProvider::configureDynamicMail()` resolved for this
  request). This is a genuine two-way email loop, not just a read-only inbox.
- **The "receive emails" toggle lives directly on this page too**, not only in Settings (explicit request:
  "where form data show there make option for receive emails") — a small form at the top of the page
  reading/writing the exact same `contact_notify_enabled`/`contact_notify_email` settings
  `Admin\Settings\GeneralManager` also exposes. Changing it in either place is immediately reflected in the
  other (both just call `Setting::set()` on the same keys).
- Route `/admin/contact-messages`, sidebar entry added to the existing CRM collapsible submenu (alongside
  Leads and Chat) — `$crmActive` in `sidebar.blade.php` extended to also match `admin.contact-messages.*`.

## Dynamic SMTP settings — "not from .env, from the database"

**No precedent existed anywhere in this codebase** for runtime-mutable mail configuration before this —
confirmed by grepping the whole `app/`/`config/` tree for `Config::set(`/`config(['mail...` and finding zero
matches. This introduces that pattern for the first time, deliberately opt-in so it can never break an
already-working `.env`-configured install:

- **New `Setting` keys**: `smtp_enabled` (bool, default `'0'`), `smtp_host`, `smtp_port` (default `'587'`),
  `smtp_username`, `smtp_password` (**encrypted**, see below), `smtp_encryption` (`tls`/`ssl`/empty, default
  `'tls'`), `smtp_from_address`, `smtp_from_name`. All seeded with safe/blank defaults in `SettingSeeder` —
  `smtp_enabled` starts `false`, so **every existing install keeps using `.env`'s `MAIL_*` variables exactly
  as before** until an admin explicitly visits SMTP Settings and turns it on.
- **`Setting::getEncrypted()`/`setEncrypted()`** (new) — encrypts the password at rest via Laravel's `Crypt`
  facade (APP_KEY-backed, the same trust boundary this app already extends to session/cookie signing).
  Deliberately **not cached** the way `Setting::get()` is (`Cache::rememberForever`) — caching a decrypted
  secret in the cache store (file/redis/whatever) in plaintext indefinitely would defeat the point of
  encrypting it in the database in the first place. It's read at most once per request (inside the mail
  config override below), so the extra query is not a real cost. A `DecryptException` (a value saved before
  encryption existed, or a rotated `APP_KEY`) fails closed — returns the given default rather than handing a
  garbage string to a real SMTP server as a password.
- **`AppServiceProvider::configureDynamicMail()`** (new, called from `boot()` on every request) — if
  `smtp_enabled` is true, overrides `config('mail.default')` to `smtp` and pushes every `smtp_*` setting
  into `config('mail.mailers.smtp.*')` / `config('mail.from.*')`. Guarded by `Schema::hasTable('settings')`
  first — without that guard, the very first `php artisan migrate` on a brand-new install would crash
  trying to query a table that doesn't exist yet, purely as a side effect of booting service providers
  before that migration has run. This is cheap on every request: `Setting::get()` is already
  `Cache::rememberForever`-backed, so after the first read it's a cache hit, not a database query.
- **The SMTP settings form lives as a tab on the main Settings page** (`Admin\Settings\GeneralManager`,
  `/admin/settings`) — originally built as its own standalone `Admin\Settings\SmtpManager` page/sidebar
  link, then merged into `GeneralManager` and deleted per a follow-up request ("move SMTP from the sidebar
  onto the Settings page as a tab"). All the SMTP properties/`saveSmtp()`/`sendTestEmail()` methods now live
  directly on `GeneralManager`; `save()` (General tab) and `saveSmtp()` (SMTP tab) remain two independent
  forms/submits, they just share one Livewire component and one page now.
  - **The tabs are Alpine-driven (`x-data="{ tab: 'general' }"`, `x-show`), not Bootstrap's native
    `data-bs-toggle="tab"`** — this was a deliberate fix, not the first thing tried. Bootstrap's tab JS
    tracks the active tab purely via DOM classes it manages itself; since both tab panes belong to the
    *same* Livewire component, submitting either form (`save()` or `saveSmtp()`) triggers a Livewire morph
    of the *whole* component, and the freshly server-rendered HTML would always mark "General" as active in
    its raw markup — snapping the visible tab back to General immediately after saving the SMTP tab. Alpine
    state on the wrapping `x-data` element survives a Livewire morph (that's the whole point of the
    Livewire+Alpine integration), so switching to `x-show` avoided the bug entirely. Same principle this
    app's own README already documents from the Module 6 chat-widget toggle bug: prefer Alpine-only local
    UI state over anything a Livewire round-trip could reset.
  - A **"Send Test Email"** button (`Mail::raw(...)` to an address the admin types in) lets SMTP credentials
    be verified without needing a real contact-form submission. The password field is **never pre-filled
    with the real decrypted secret** (same principle as any login form never showing your current password
    back to you) — it shows a "Set" badge if a password already exists, and leaving the field blank on save
    means "keep the current password," not "clear it." Saving without `smtp_enabled` only requires the
    always-nullable fields to validate; saving *with* it enabled additionally requires host/port/from-address
    to be non-empty (a half-filled "enabled" config would otherwise silently break every email the site sends).
- **Verified directly** (not just "it renders"): set real-looking SMTP values via `Setting::set()`/
  `setEncrypted()` in tinker, re-invoked `AppServiceProvider::boot()`, and confirmed
  `config('mail.mailers.smtp.*')` picked up every value correctly **including the password decrypting back
  to the original plaintext** — then reset `smtp_enabled` back to `false` and confirmed `config('mail.default')`
  reverted to reading `.env`'s value again.

## Tests

None added (consistent with every other "move fast" module in this app — see modules 5–12). Verified via:
live HTTP round-trip against the running dev server for the plain-HTML contact form (real CSRF token +
session cookie, real POST, confirmed the DB row and the rendered success message); `Livewire::test()` for
`Admin\ContactMessages\Manager` (submission appears in the list with an unread badge, `view()` marks it
read, `sendReply()` sends a real email — confirmed via the `log` mailer's captured output showing the
correct `To:`/`Subject:` headers) and the merged `Admin\Settings\GeneralManager` SMTP tab (renders both
tabs, `saveSmtp()` and `save()` both persist correctly as independent actions on the same component); a direct
`AppServiceProvider::boot()` re-invocation to confirm the dynamic mail config override (see above). If you
want automated coverage later, natural cases mirror `InquiryFormTest`: a guest can submit the plain-HTML
contact form and a `ContactMessage` row is created; `ContactNotifier` sends to the configured address and
falls back to `contact_email` when `contact_notify_email` is blank; `contact_notify_enabled = false`
suppresses the email entirely while still saving the message; `configureDynamicMail()` leaves `.env`'s mail
config alone when `smtp_enabled` is false and overrides it correctly when true.
