# Module 1: Roles & Authentication

**Status:** Done. **Acceptance:** each role logs in, lands on correct dashboard; unauthorized routes blocked. ✅
(Verified live via browser for all 5 demo roles + 30 automated tests at the time.)

## What was built

- **Auth backend:** Laravel Fortify (headless — no Breeze scaffolding). Config: `config/fortify.php`
  (features enabled: registration, resetPasswords, emailVerification, updateProfileInformation,
  updatePasswords — 2FA/passkeys explicitly disabled, not needed).
- **Custom Bootstrap/Alpine auth views** (Fortify normally ships no views): `resources/views/auth/*`
  (`layout`, `login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`).
  Wired up in `app/Providers/FortifyServiceProvider.php` via `Fortify::loginView()` etc.
- **Role-based redirect after login/register:** custom `LoginResponse`/`RegisterResponse` bindings in
  `FortifyServiceProvider::register()` call `$request->user()->dashboardRoute()`.
- **Roles:** `App\Enums\RoleName` (`super-admin`, `admin`, `agent`, `agency`, `user`), seeded by
  `RoleSeeder`. Only `user`, `agent`, `agency` are self-registrable
  (`RoleName::registrable()`); admin/super-admin are seed-only, enforced in
  `app/Actions/Fortify/CreateNewUser.php` (validates `role` against the registrable list).
- **`User` model additions:** `phone`, `agency_name`, `agency_license_no`, `agency_address` columns
  (migration `add_profile_fields_to_users_table`), `HasRoles`, `HasMedia`/`InteractsWithMedia` (avatar,
  singleFile collection `avatar`), `dashboardRoute()` helper (role → route name lookup), implements
  `MustVerifyEmail`.
- **Profile edit:** `Frontend\ProfileController@edit/update` (`/profile`), delegates validation/update to
  Fortify's `UpdateUserProfileInformation` action (customized to also handle phone/agency fields + avatar
  upload via medialibrary).
- **Route groups + middleware:** `role` middleware alias registered in `bootstrap/app.php` (package doesn't
  auto-register it). `/admin/*` → `role:super-admin|admin`, `/agent/*` → `role:agent|agency`,
  `/account/*` → `role:user`, all stacked with `['auth','verified']`.
- **Dashboards:** `Admin\DashboardController` (`/admin`), `Frontend\AgentDashboardController` (`/agent`),
  `Frontend\AccountDashboardController` (`/account`) — the latter two got real content in later modules
  (My Listings summary in Module 3, favorites/inquiries counts in Module 4).
- **Demo accounts** (`DemoUserSeeder`): `{role}@shinestarmarketing.test` / `password` for all 5 roles,
  `email_verified_at` pre-set so they can log in immediately.

## Gotchas / things to know

- Mailtrap is configured as the dev mailer in `.env.example` but **you must fill in real Mailtrap
  credentials** in `.env` for verification/reset emails to actually send. Until then `MAIL_MAILER=log`
  writes them to `storage/logs/laravel.log` instead — fine for functional testing.
- Registration form (`resources/views/auth/register.blade.php`) uses Alpine (`x-data`/`x-show`) to reveal
  agency fields only when "Agency" is selected as account type.
- `app/Http/Controllers/Controller.php` had to manually add `use AuthorizesRequests;` — Laravel 11's
  skeleton base controller doesn't include it by default, but `$this->authorize()` in Module 3+ controllers
  needs it.

## Tests

`tests/Feature/Auth/{RegistrationTest,LoginRedirectTest,RoleAccessTest}.php` — registration role
restriction, per-role login redirect, middleware blocking for every role combination.
