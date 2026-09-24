# Migration Guide — Moving Shine Star Marketing to a New Server

> Installing onto cPanel/shared hosting from scratch (rather than migrating an existing install)?
> Follow **`docs/DEPLOYMENT.md`** — it's the short build → upload → verify checklist and includes an
> "assets not loading" troubleshooting table; the steps below are the fuller version of the same job.

This app stores its content two ways: **database rows** (pages, header/menu/footer, CDN links,
properties, projects, blog posts, reviews, settings, users, chat history — everything editable from the
admin panel) and **uploaded files on disk** (property/project photos, brochures, review photos, the site
logo, blog images, chat attachments — anything uploaded through a form). A full migration needs the
project files, a database export, and the uploaded-files folder. Missing any one of the three leaves the
new site partially broken (usually: page looks right but every image is a broken link, or nothing loads
because the database has no data at all).

## The three things you need

1. **The project files** — copy the whole project folder to the new server, or clone/pull the same
   repository there. Either way, `vendor/` and `node_modules/` do **not** need to come along — they get
   rebuilt on the new server in the steps below (and `vendor/` especially will contain OS-specific
   binaries from certain packages that won't work if literally copied between different operating
   systems).
2. **A database backup** — Admin → Settings → **Backup & Migration** → "Download Database Backup". This
   is a plain `.sql` file containing every table's schema and every row, generated in pure PHP (no
   dependency on the `mysqldump` binary being installed on either server).
3. **A media files backup** — same page, "Download Media Files Backup". A zip of everything under
   `storage/app/public` (every uploaded file). The database only stores *paths* to these files, never the
   file bytes, so this step is not optional — skip it and every image on the new site will 404.

## Step-by-step

### 1. Check the new server is actually ready

Before touching anything, visit Admin → Settings → **System Requirements Check** — ideally on the new
server itself, once the project files are copied there and it can at least boot (it works even without a
working database connection; that check just reports itself as failing rather than crashing the page).
It checks:

- PHP version (this app requires **PHP 8.2+**)
- Required PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
  `curl`, `fileinfo`, `gd`, `exif`, `zip`, `bcmath`, `dom`, `filter`, `session`
- Composer dependencies installed (`vendor/autoload.php` present)
- `.env` file present, `APP_KEY` set
- Database connection working
- `storage/` and `bootstrap/cache/` are writable by the web server
- `public/storage` symlink exists
- Frontend assets are built (`public/build/manifest.json` present)
- Pending migrations
- Reverb (live chat) configuration

Fix anything it flags red before continuing — most failures here are the actual root cause if the site
"doesn't work" after a migration, not a database/file problem.

**The most commonly missing piece**: the `exif` PHP extension. It's disabled by default in some PHP
distributions but is required by Spatie Media Library (the package behind every image/file upload in this
app) — without it, uploading or processing any image throws an error. Enable it in `php.ini`
(`extension=exif`, no dot) and restart PHP/the web server.

### 2. Set up the new server

```bash
# Copy .env (or copy .env.example and fill it in fresh) — update at minimum:
#   DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_HOST  (the new server's database)
#   APP_URL                                          (the new domain)
#   MAIL_* settings if outgoing email should work
#   REVERB_* settings if live chat should work (see below)
cp .env.example .env   # or copy your existing .env over, then edit the values above

composer install --no-dev --optimize-autoloader
npm install
npm run build

php artisan key:generate   # only if APP_KEY is empty in the .env you're using
```

### 3. Create the database and import the backup

```bash
# Create an empty database first (name must match DB_DATABASE in .env)
mysql -u USERNAME -p -e "CREATE DATABASE your_database_name;"

# Then import the backup downloaded from Admin → Settings → Backup & Migration
mysql -u USERNAME -p your_database_name < shine-star-marketing-db-YYYY-MM-DD_HHMMSS.sql
```

(Or use phpMyAdmin's "Import" tab if you don't have shell/CLI access to the database.)

### 4. Extract the media files backup

Unzip `shine-star-marketing-media-YYYY-MM-DD_HHMMSS.zip` **into the project root** — it's built so its
internal folder structure already matches `storage/app/public`, so extracting it there recreates every
upload in its original location. Confirm afterward that `storage/app/public/<some-known-file>` exists.

### 5. Finish the Laravel setup

```bash
php artisan storage:link       # creates the public/storage symlink — images 404 without this
php artisan migrate --force    # safe even though the data is already imported; confirms nothing pending
php artisan config:cache
php artisan route:cache
```

### 6. Start Reverb (only if you want live chat push notifications)

```bash
php artisan reverb:start
```

Nothing in this app starts Reverb automatically — without it running, chat still works completely
normally, it just falls back to polling every 5–15 seconds instead of updating instantly (this is a
deliberate, graceful degradation, not a bug — see `docs/modules/module-06-chat.md` if you're curious how).
In production, run this under a process supervisor (systemd, Supervisor, PM2, etc.) so it restarts if it
crashes or the server reboots — it's a plain long-running PHP process, not a request/cron-driven job.

### 7. Re-run the System Requirements Check

Once the site is up, open Admin → Settings → System Requirements Check again — everything should now
show "Pass" (Reverb will show "Pass" for configuration even if the process itself isn't running yet;
that specific check can only confirm the *credentials* are set, not that the process is live).

## Things that are easy to forget

- **File permissions on Linux**: `storage/` and `bootstrap/cache/` need to be writable by whatever user
  your web server runs as (often `www-data`). `chmod -R 775 storage bootstrap/cache` plus setting the
  correct group ownership is the usual fix if the System Check page flags these.
- **The `MEDIA_DISK` setting**: this app defaults to storing uploads on the local `public` disk. If you
  intend to switch to Cloudinary on the new server (`cloudinary-labs/cloudinary-laravel` is already
  installed), set `MEDIA_DISK=cloudinary` and `CLOUDINARY_URL=...` in `.env` — but do this as a deliberate
  choice, not by accident, since it changes where *newly uploaded* files go without moving anything
  already restored from the media backup.
- **Don't run `php artisan migrate:fresh`** on the new server after importing the database backup — that
  drops every table first. `php artisan migrate` (no `:fresh`) is safe and just confirms there's nothing
  left to run.
- **Ad hoc data added outside a seeder**: this codebase's own module docs (`docs/modules/README.md`) flag
  several places where real content was added via one-off `php artisan tinker` scripts rather than a
  registered seeder class. None of that matters for *this* migration path — a full database backup
  captures every row regardless of how it originally got there — but it does mean a bare
  `php artisan migrate:fresh --seed` on a brand-new install (skipping the backup/restore entirely) will
  **not** reproduce things like the real project photos fetched from developers' own websites, or the
  demo property photos sourced from Pexels — those only exist because a database backup carries them
  forward. If you're setting up a **new, from-scratch dev environment** rather than migrating the live
  site, `migrate:fresh --seed` alone gives you working demo data, just without those specific media files.
