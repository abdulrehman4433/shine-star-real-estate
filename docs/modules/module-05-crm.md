# Module 5: CRM Module

**Status:** Done. **Acceptance:** inquiry becomes lead automatically; agent updates status, adds notes,
gets reminders. ✅ Verified by re-running the full existing suite (75/75 still passing — confirms the new
`PropertyInquiryObserver` doesn't break inquiry submission) plus a direct DB check that seeding an inquiry
produced a lead, activity, and task correctly. **No new dedicated Livewire tests were written for this
module and no live browser walkthrough was done — per explicit user request to move fast; user is
testing this module manually.**

## What was built

- **`leads` table:** `property_id`/`property_inquiry_id` nullable (`nullOnDelete`), `assigned_to` nullable
  FK users (`nullOnDelete` — unassigned leads show as "Unassigned"), `name`, `contact` (single string —
  holds the inquiry's email; there's no separate phone column on `leads` itself, `notes` (free-text, copied
  from the originating inquiry's message), `source` (defaults `website_inquiry`), `status`
  (`App\Enums\LeadStatus`: new/contacted/qualified/negotiating/won/lost).
- **`lead_activities` table:** timeline entries — `lead_id` (cascade), `user_id` nullable (who logged it),
  `type` (call/meeting/note/follow_up, free string not an FK), `notes`, `occurred_at`.
- **`tasks` table:** `lead_id` nullable (cascade — general reminders not tied to a lead are possible but
  not exposed in the UI yet), `assigned_to` FK users (cascade), `title`/`description`, `due_date`,
  `is_completed` + `completed_at`, and **`reminded_at`** (nullable — prevents the due-reminder command from
  emailing the same task twice).
- **Auto-create-lead-from-inquiry:** `App\Observers\PropertyInquiryObserver::created()`, registered in
  `AppServiceProvider::boot()` via `PropertyInquiry::observe(...)`. Fires on **every** `PropertyInquiry`
  creation (Module 4's guest and authenticated inquiry paths both trigger it automatically — no changes
  needed in `InquiryForm`). Maps `email` → `contact`, `message` → `notes`, leaves `assigned_to` null
  (admin assigns later).
- **`App\Policies\LeadPolicy`:** `view()`/`update()` — admin/super-admin can access any lead; an
  agent/agency can only access leads where `assigned_to === $user->id`. `assign()` — admin/super-admin
  only (used both for the mount-time gate on the admin manager and the actual assign action).
- **Admin:** `Admin\Leads\Manager` (`/admin/leads`) — table of all leads, status filter, and a **status
  select in the same row** that fires `assign($leadId, $agentId)` on change (`wire:change` reading
  `$event.target.value`, not a separate submit button). Assigning sends `LeadAssigned` mail to the newly
  assigned agent. Agent options are `User`s with role `agent` or `agency`.
- **Agent:** `Frontend\Leads\MyLeads` (`/agent/leads`) — read-only list scoped to
  `Lead::assignedTo(auth()->id())`, status filter, links to the shared detail page.
- **Shared detail page:** `Frontend\Leads\ShowLead` (`/leads/{lead}`, top-level route, `['auth','verified']`
  only — no role middleware, since both admin and the assigned agent need access; **the actual access
  control is the `LeadPolicy::view()` check in `mount()`**). Three things happen here:
  1. **Status update** — dropdown + button, calls `updateStatus()`.
  2. **Activity timeline** — add-activity mini-form (type select + notes) at the top, chronological list
     below (`$lead->activities()` is `->latest()` in the model).
  3. **Tasks** — checklist with inline checkbox-toggle-complete (`toggleTaskComplete`), add-task mini-form
     (title/description/due date) below it. New tasks default `assigned_to` to the lead's current assignee,
     falling back to whoever is adding the task if the lead is unassigned.
- **Reminder notifications:**
  - `App\Notifications\LeadAssigned` — sent immediately when an admin assigns/reassigns a lead
    (`Admin\Leads\Manager::assign()`).
  - `App\Notifications\TaskDueReminder` — sent by a new artisan command,
    **`php artisan app:send-due-task-reminders`**, scheduled `dailyAt('08:00')` in `routes/console.php`.
    The command queries `Task::dueForReminder()` (not completed, `due_date <= today`, `reminded_at` still
    null), notifies each task's assignee, then stamps `reminded_at` so it's never sent twice. **This
    requires the Laravel scheduler to actually be running** (`php artisan schedule:work` in dev, or a real
    cron entry calling `php artisan schedule:run` every minute in production) — nothing fires on its own
    otherwise.
- **Nav wiring:** admin sidebar's "Leads (CRM)" placeholder now links to `admin.leads.index`; agent
  dashboard's "Leads" card links to `agent.leads.index`.
- **`LeadSeeder`:** creates one real `PropertyInquiry` (so the observer fires naturally, not a
  hand-crafted `Lead::create()`), assigns the resulting lead to the demo agent, and adds one sample
  activity + one sample task (due in 2 days, so the reminder command correctly does *not* fire for it yet).
  Guarded by `if (Lead::query()->exists()) return;` for idempotent re-seeding.

## Gotchas / things to know

- `leads.contact` is a single string field, not separate email/phone columns — this matches the spec's
  literal column list. The full inquiry (with separate email/phone) is still reachable via
  `$lead->inquiry` if needed.
- `LeadActivity.type` and nothing else constrains its value at the DB level — validation only happens in
  `ShowLead::addActivity()` (`in:call,meeting,note,follow_up`). Adding a new activity type = update that
  validation rule + the `<select>` options in the Blade view, no migration needed.
- The due-task-reminder command is **not wired to run automatically** in this local XAMPP setup — there's
  no cron/Task Scheduler entry pointing at `schedule:run`. For real reminders to go out you need either
  `php artisan schedule:work` running in a terminal during development, or a Windows Task Scheduler /
  cron job in production hitting `php artisan schedule:run` every minute.
- Nothing prevents an admin from being "assigned" a lead through the `Manager` component (the agent
  dropdown only lists agent/agency-role users, so this can't happen through the UI, but there's no DB-level
  constraint either).

## Tests

None added this module (explicitly skipped per user request to move quickly). What *was* checked: the
full pre-existing 75-test suite still passes unmodified, and a direct MySQL query confirmed
`LeadSeeder` → observer → lead/activity/task chain produces correct rows. If you want test coverage added
later, the natural cases mirror Module 3/4's patterns: `LeadPolicy` view/update scoping (admin vs.
assigned-agent vs. unrelated-agent), `assign()` sending `LeadAssigned` (`Notification::fake()`),
`ShowLead`'s three actions (status/activity/task), and `SendDueTaskReminders` respecting `reminded_at`
(call it twice, assert only one notification).
