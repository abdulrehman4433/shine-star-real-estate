# Shared-hosting extras

Main deploy steps: [DEPLOYMENT.md](DEPLOYMENT.md). This page only covers the extra settings that
shared hosting needs — do these after the main steps work.

1. **PHP version/extensions** — cPanel → "Select PHP Version": PHP **8.2+**, and enable
   `pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl, fileinfo, gd, exif, zip, bcmath,
   dom, filter, session`. `exif` and `zip` are the two usually missing (no `exif` = image uploads
   fail; no `zip` = media backup download fails).

2. **Cron job** (cPanel → Cron Jobs, every minute) — needed for the daily task-reminder email:
   ```
   * * * * * php /home/USER/public_html/laravel/artisan schedule:run >> /dev/null 2>&1
   ```
   Use the exact `php` and `artisan` paths cPanel shows you.

3. **Queue** — set `QUEUE_CONNECTION=sync` in `.env`. Shared hosting can't run the background
   worker a database queue needs; with `database` and no worker, jobs pile up forever.

4. **Live chat push (Reverb)** — cannot run on shared hosting (needs a long-running process on a
   custom port). Chat still works: it polls every 5–15 s instead of pushing instantly. Don't try to
   fix it here — it's a reason to move to a VPS, not a config problem.

5. **Email** — don't edit `.env` for mail. Use **Admin → Settings → SMTP Settings**, fill in your
   host's outgoing-mail relay, save, click "Send Test Email".

6. **SSL** — cPanel → AutoSSL (free). Then `APP_URL` must be `https://…` (see DEPLOYMENT.md step 5),
   otherwise mixed-content/broken-asset warnings.

7. **Permissions** — `laravel/storage/` and `laravel/bootstrap/cache/` must be writable by your PHP
   user. `755`/`644` is normally enough on shared hosting — don't use `777`.

8. **Host blocks `symlink()`?** (step 6 of the main guide returns false) → set in `.env`:
   `MEDIA_DISK=cloudinary` + `CLOUDINARY_URL=…`. New uploads then go to Cloudinary with no symlink
   needed; files already uploaded locally still need the link (or re-upload them).

9. **After every deploy, verify:**
   - `https://yourdomain.com/build/manifest.json` → raw JSON
   - **Admin → Settings → System Requirements Check** → all rows pass
