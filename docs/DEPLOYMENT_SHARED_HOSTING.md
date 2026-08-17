# Deploying to Shared Hosting (cPanel and similar)

This guide is specifically for **shared hosting** — cPanel, Plesk, or similar control-panel hosting where
you don't have root access, may not have SSH or Composer/Node available, and can't run persistent
background processes. It assumes you've already read `MIGRATION_GUIDE.md` in the project root (which
covers the general "move the files + import a database backup" process) — this doc adds the parts that are
**specific to shared hosting's constraints**, which a normal VPS/cloud deployment doesn't need to worry
about at all.

Before starting, run **Admin → Settings → System Requirements Check** on your current working install so
you know exactly what PHP version/extensions this app needs, then confirm your hosting plan actually offers
them (most shared hosts let you pick a PHP version per-domain in cPanel's "Select PHP Version"/"MultiPHP
Manager" tool — do this first, since changing it later can require re-checking everything below).

## The one thing that makes shared hosting different: the document root

A Laravel app is designed to have its web server point **only** at the `public/` folder — everything else
(`app/`, `config/`, `.env`, `vendor/`, the whole framework) must sit **outside** the publicly-served
directory, or anyone could browse to `yoursite.com/.env` and read your database password.

Most shared hosting accounts, by default, serve your domain directly from `public_html/` — there's no
"point this domain at a subfolder's `public/` directory" option on cheaper plans. You have three real
options, in order of preference:

1. **Ask your host to set the document root to a subfolder** (e.g. upload everything to
   `~/shine-star-marketing/`, then ask support — or use cPanel's "Domains" tool if it exposes this — to
   point the domain's document root at `~/shine-star-marketing/public`). Many mid-tier and higher shared
   plans support this. This is the cleanest option and needs no code changes at all.
2. **Use a subdomain that cPanel lets you assign a custom document root to.** Same idea as #1, just via
   the "Subdomains" tool instead of the primary domain — useful if you want to keep your main domain's
   `public_html` untouched for something else.
3. **The classic shared-hosting trick, if neither of the above is available**: upload the whole project
   **above** `public_html` (e.g. to `~/shine-star-marketing/`, a sibling of `public_html`, not inside it),
   then copy — not move — the *contents* of `shine-star-marketing/public/` into `public_html/`, and edit
   the two `require`/`require_once` lines in `public_html/index.php` so they point up and across into the
   real app instead of assuming they're still inside `public/`:

   ```php
   // public_html/index.php — change these two lines:
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';

   // to (adjust the folder name to whatever you actually named it):
   require __DIR__.'/../shine-star-marketing/vendor/autoload.php';
   $app = require_once __DIR__.'/../shine-star-marketing/bootstrap/app.php';
   ```

   Every time you rebuild frontend assets (`npm run build`) or the app updates, you'll need to re-copy the
   *contents* of `public/` into `public_html/` again — this is the real downside of this approach, which is
   why options 1–2 are worth asking your host about first.

## Step-by-step

### 1. Build everything that needs Node.js locally, first

Shared hosting almost never has Node.js/npm available. Run this **on your own machine**, not the server:

```bash
npm install
npm run build
```

This produces `public/build/` (compiled CSS/JS with hashed filenames + `manifest.json`) — this folder gets
uploaded as plain static files; the server never needs to run `npm` at all.

### 2. Get Composer dependencies ready

- **If your host gives you SSH access with Composer available** (check cPanel's "Terminal" feature, or ask
  support): upload the project, then run `composer install --no-dev --optimize-autoloader` on the server
  directly. This is the simplest path — skip to step 3.
- **If there's no SSH/Composer on the server**: run `composer install --no-dev --optimize-autoloader`
  **locally**, then upload the resulting `vendor/` folder along with everything else. It's large (expect
  tens of thousands of files) — upload it as a single `.zip` through cPanel's File Manager and extract it
  server-side rather than uploading file-by-file over FTP, which can take hours and is far more likely to
  fail partway through.

### 3. Upload the project files

Upload everything **except**: `node_modules/`, `.git/`, `tests/`, and your local `.env` (you'll create a
fresh one in step 5). Where you upload it to depends on which document-root option you picked above.

### 4. Create the database

Use cPanel's "MySQL Databases" tool: create a database, create a user, and add that user to the database
with all privileges. **cPanel typically prefixes both the database name and username with your cPanel
account username** (e.g. `myuser_shinestar`, not just `shinestar`) — use the exact prefixed names it gives
you in step 5's `.env`, not the names you originally typed in.

### 5. Set up `.env`

Copy `.env.example` to `.env` on the server and fill in, at minimum:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myuser_shinestar
DB_USERNAME=myuser_shinestar
DB_PASSWORD=whatever-you-set-in-cpanel
```

Then generate a real application key — via SSH: `php artisan key:generate`. **If you have no SSH at all**,
generate one locally instead (`php artisan key:generate --show` on your own machine prints the value
without writing it) and paste the resulting `base64:...` string into `APP_KEY=` by hand.

Leave the `MAIL_*` variables alone for now — see "Outgoing email" below, you likely won't need to touch
them at all.

### 6. Get the database schema + data onto the server

You have two paths here, and **which one you use depends entirely on whether you have SSH access with
Composer/PHP CLI working**:

- **With SSH**: run `php artisan migrate --force` directly on the server. For a truly fresh install, add
  `--seed` too (only if you want the bundled demo data — skip it for a real production launch).
- **Without SSH** (the common case on basic shared plans): do this from your own machine or a staging
  environment where you *do* have full CLI access. Get the app fully working there (migrations run, real
  content added), then go to **Admin → Settings → Backup & Migration** and download the **Database
  Backup** — a plain `.sql` file needing nothing but a MySQL client to import, generated in pure PHP with
  **no dependency on the `mysqldump` binary** (see `docs/modules/module-12-notifications-settings.md` for
  why that matters). Import that `.sql` file into the shared host's database through **phpMyAdmin's Import
  tab** (cPanel exposes phpMyAdmin directly — no SSH needed for this step at all). This is the most
  reliable way to get a fully-migrated-and-seeded database onto a host where you can't run `artisan`
  yourself.

Either way, also download the **Media Files Backup** (.zip) from that same page and extract it into
`storage/app/public` on the server (via cPanel File Manager's "Extract" feature) — the database only stores
file *paths*, so without this step every property/project photo will be a broken image link.

### 7. Storage symlink

`public/storage` needs to be a symlink pointing at `storage/app/public`, or every uploaded image 404s.

- **With SSH**: `php artisan storage:link`.
- **Without SSH**: most cPanel File Managers have a "Create Symlink" option — right-click inside
  `public/`, create a symlink named `storage` pointing at `../storage/app/public` (adjust the relative path
  if you used the option-3 document-root trick above, since `public/` there is really `public_html/`).
  **If your host disables PHP's `symlink()` function entirely** (a small number do, for security), the
  simplest fix is switching to Cloudinary instead of local storage — `cloudinary-labs/cloudinary-laravel`
  is already a dependency; set `MEDIA_DISK=cloudinary` and `CLOUDINARY_URL=...` in `.env` and every new
  upload goes there instead, no symlink needed at all (this doesn't retroactively move already-uploaded
  local files, though — decide this before go-live if possible).

### 8. File permissions

`storage/` and `bootstrap/cache/` need to be writable by the user PHP runs as. On most shared hosting (PHP
running via suPHP/FastCGI as your own cPanel user, not a shared `www-data`), standard `755` permissions
already work correctly since the files are already owned by you — you generally do **not** need `777`
here, and setting it that permissively is a real security downgrade worth avoiding on a host you share with
other tenants. If uploads/logs fail with a permissions error after everything else is set up, check
ownership before reaching for `777`.

### 9. Cron job — required, not optional, for this app

This app uses Laravel's scheduler for one thing: a daily task-due-reminder email
(`Schedule::command('app:send-due-task-reminders')->dailyAt('08:00')` in `routes/console.php`). Without a
cron entry, that reminder simply never fires — nothing else breaks, but it's easy to forget since the site
otherwise looks completely fine without it. Add this in cPanel's "Cron Jobs" tool, running every minute
(Laravel's scheduler itself decides what actually needs to run each time it's invoked):

```
* * * * * php /home/youruser/shine-star-marketing/artisan schedule:run >> /dev/null 2>&1
```

Use the **absolute path** cPanel shows you for `php` and for your project — a relative path or the wrong
PHP version binary is the single most common reason a cron job "does nothing" on shared hosting.

### 10. Queue configuration — recommend switching to `sync` for shared hosting

This app's `.env` defaults to `QUEUE_CONNECTION=database`, and the only thing actually queued anywhere in
the codebase is sitemap regeneration (`App\Jobs\GenerateSitemap`, dispatched after saving a blog post or
CMS page). Every email notification in this app sends **synchronously**, not through the queue — so queue
configuration only affects sitemap freshness, nothing else.

`database`-driven queues need a worker process (`php artisan queue:work`) continuously running to actually
process anything — and **persistent background processes are exactly what shared hosting doesn't allow**.
Left as `database` with no worker running, queued sitemap jobs will just accumulate, unprocessed, in the
`jobs` table forever. For shared hosting, set:

```
QUEUE_CONNECTION=sync
```

This runs the (cheap, fast) sitemap job immediately inline instead of queueing it — there's no real
downside for this specific job, and it completely sidesteps needing a worker process shared hosting can't
provide anyway.

### 11. Live chat (Reverb) — will not work on typical shared hosting, and that's fine

`php artisan reverb:start` needs a persistent, long-running process bound to a custom port — something
essentially no shared hosting plan allows. **Don't try to make this work on shared hosting.** The chat
feature already degrades gracefully without it: guests and logged-in users both fall back to polling every
5–15 seconds instead of instant push (see `docs/modules/module-06-chat.md`) — messages still arrive, just
not instantly. If real-time push matters enough to be worth it, that specifically is a reason to consider a
VPS instead of shared hosting for this app, not a shared-hosting configuration problem to solve.

### 12. Outgoing email — use the app's own SMTP settings, not `.env`

Most shared hosting plans include their own outgoing mail relay with credentials specific to your hosting
account. Rather than editing `.env`'s `MAIL_*` variables (which requires file access every time credentials
change), use **Admin → Settings → SMTP Settings tab** — this app stores SMTP configuration in the database
and applies it at runtime, specifically so this kind of change can be made by whoever manages the site day
to day, without touching server files at all (see `docs/modules/module-15-contact-messages.md` for the full
mechanism). Turn on "Enable custom SMTP," fill in your host's mail relay details, save, then use the
built-in "Send Test Email" button to confirm it actually works before relying on it for real contact-form
notifications.

### 13. SSL

Use cPanel's AutoSSL (usually automatic and free, via Let's Encrypt) rather than paying for a certificate
unless your host doesn't offer it. Once HTTPS is active, make sure `APP_URL` in `.env` uses `https://` —
this app's asset/URL generation follows `APP_URL`, so a mismatched scheme here is a common cause of mixed
HTTP/HTTPS asset warnings after enabling SSL.

## After deploying: verify before calling it done

1. Visit **Admin → Settings → System Requirements Check** on the live site — every row should read
   "Pass." Anything short of that (a missing PHP extension, `public/storage` not linked, assets not built,
   `.env`/`APP_KEY` missing) is exactly what this page exists to catch before it becomes a confusing bug
   report later.
2. Submit the `/contact-us` form once for real and confirm the message shows up under **CRM → Contact
   Messages**, and that a notification email actually arrives (check spam first) if email notifications are
   enabled.
3. Log in as an admin and spot-check a few pages that touch file uploads (a property/project detail page
   with photos) to confirm the storage symlink is actually working, not just present.
4. If you're relying on the daily task-reminder email, don't wait a full day to find out the cron entry has
   a typo — cPanel's Cron Jobs tool usually shows an execution log; check it ran at all after the first
   scheduled time passes.
