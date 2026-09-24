# Deploy to cPanel — step by step

Your layout: public files directly in `public_html/`, app code in `public_html/laravel/`.
DB is already imported, so this ends at "assets + media working".

## Final layout (most errors = one file in the wrong half)

```
public_html/
├── .htaccess            ← copy of laravel/public/.htaccess
├── index.php            ← copy of laravel/public/index.php, 3 lines edited (step 4b)
├── build/               ← copy of laravel/public/build/     ← browser loads CSS/JS from HERE
├── images/  favicon.ico  robots.txt  sitemap.xml             ← rest of laravel/public/*
├── storage  →  laravel/storage/app/public   (symlink, step 6)
├── .htaccess            ← blocks access to laravel/.env (step 4c)
└── laravel/             ← whole project: app, bootstrap, config, database, public, resources,
                            routes, storage, vendor, artisan, .env …
    └── public/build/     ← KEEP IT HERE TOO. Laravel reads manifest.json from here.
```

**Rule to remember:** browser fetches `/build/...` from `public_html/build/` **and** Laravel reads
the manifest from `laravel/public/build/manifest.json`. Both copies must exist. Media loads from
`public_html/storage` → symlink → `laravel/storage/app/public`.

---

## 1. Commands to run on your PC (before the zip)

```bat
cd /d "E:\my projects\shine-star-real-estate"
npm install
npm run build
composer install --no-dev --optimize-autoloader
```

`npm run build` creates `public\build\` **and** deletes a stale `public\hot` file (if `npm run dev`
was left running, that file makes the live site load assets from `localhost:5173` — a classic
"no styles on the server" cause).

## 2. Zip the project, excluding these

**Exclude:** `node_modules`, `.git`, `tests`, `.env`, `.env.production`, `public\hot`,
`storage\logs`, `storage\framework\cache`, `storage\framework\sessions`, `storage\framework\views`,
`storage\app\backups`, `bootstrap\cache\packages.php`, `bootstrap\cache\services.php`

**Must be inside:** `vendor\`, `public\build\`, `public\.htaccess`, everything else.

Zip it in Explorer/7-Zip while skipping the list above, **or** run this in the project folder
(it excludes automatically and writes `Desktop\shine-star-deploy.zip`):

```powershell
$src = (Get-Location).Path
$out = "$env:USERPROFILE\Desktop\shine-star-deploy.zip"
$skipDirs  = @('node_modules','.git','tests','storage\logs','storage\framework\cache','storage\framework\sessions','storage\framework\views','storage\app\backups')
$skipFiles = @('.env','.env.production','public\hot','bootstrap\cache\packages.php','bootstrap\cache\services.php')
Add-Type -AssemblyName System.IO.Compression.FileSystem
if (Test-Path $out) { Remove-Item $out -Force }
$zip = [System.IO.Compression.ZipFile]::Open($out,'Create')
Get-ChildItem -Recurse -Force -File | ForEach-Object {
    $rel = ($_.FullName.Substring($src.Length + 1)) -replace '\\','/'
    $skip = ($skipDirs | Where-Object { $rel -like "$_\*" }).Count -gt 0 -or ($skipFiles -contains $rel)
    if (-not $skip) { [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $rel) | Out-Null }
}
$zip.Dispose()
"Created: $out"
```

## 3. Upload + extract

1. Upload the zip → **extract server-side** (File Manager → Upload → Extract), so nothing gets
   half-copied and no `build\build\` nesting is created.
2. Result: the project sits at `public_html/laravel/`.

## 4. Public files into `public_html/` (the correct way)

**a)** Copy the **contents** of `public_html/laravel/public/` into `public_html/`:
`.htaccess`, `index.php`, `build/`, `images/`, `favicon.ico`, `robots.txt`, `sitemap.xml`.
Do **not** delete `laravel/public/` afterwards — its `build/` folder must stay (manifest).

**b)** Edit `public_html/index.php`, change these 3 paths to reach into `laravel/`:

```php
if (file_exists($maintenance = __DIR__.'/../laravel/storage/framework/maintenance.php')) {

require __DIR__.'/../laravel/vendor/autoload.php';
$app = require_once __DIR__.'/../laravel/bootstrap/app.php';
```

**c)** Create `public_html/laravel/.htaccess` with exactly this (otherwise `.env` — your DB password —
is downloadable at `https://yourdomain.com/laravel/.env`):

```apache
<FilesMatch "^\.env">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Deny from all
    </IfModule>
</FilesMatch>
```

**d)** Delete `public_html/laravel/public/hot` if it exists (and `public_html/hot` too).

## 5. `.env` — only these lines matter (DB is already imported)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com      ← no /laravel, no /public, must be https after SSL
QUEUE_CONNECTION=sync
```
`APP_URL` builds every media URL — wrong value = broken images on an otherwise fine site.

## 6. Media (uploaded photos)

1. Files must exist under `public_html/laravel/storage/app/public/…`
   (not there? upload the **Media Files Backup** zip from Admin → Settings → Backup & Migration and
   extract it there).
2. Create the symlink `public_html/storage` → `laravel/storage/app/public`:
   - if you have Terminal/SSH: `cd public_html && ln -s laravel/storage/app/public storage`
   - no SSH: upload this as `public_html/make-link.php`, visit it once in the browser, then **delete
     the file**:
     ```php
     <?php
     if (file_exists(__DIR__.'/storage')) { echo 'storage already exists — delete the real folder first'; }
     else { var_dump(symlink(__DIR__.'/laravel/storage/app/public', __DIR__.'/storage')); }
     ```
   - if an old real `storage` **folder** exists in `public_html`, delete it first (it shadows the link).
3. Test: open any broken image's URL → must return the image.

## 7. Fix assets — run these checks in order (your current error)

| # | Open in browser | Must show | If not |
|---|---|---|---|
| 1 | `https://yourdomain.com/build/manifest.json` | raw JSON | `public_html/build` missing / nested `build/build` / wrong name → delete it and re-copy `laravel/public/build` → `public_html/build` |
| 2 | view-source → copy the `/build/assets/app-….css` URL → open it | CSS text, status 200 | returns HTML/404 → same fix as #1 (request fell into `index.php`) |
| 3 | DevTools → Network: does any asset say `localhost:5173`? | no | delete `laravel/public/hot` (step 4d) |
| 4 | still stale after a re-upload? | — | Ctrl+F5 — a cached page references old hashed filenames |

Also confirm `public_html/.htaccess` is the one from this project (it carries the MIME-type block
that browsers need to run `<script type="module">`).

## 8. Final check

Log in → **Admin → Settings → System Requirements Check** → every row must pass. It verifies
exactly the things above: manifest present, every file in the manifest present on disk, no `hot`
file, `APP_URL` matches the live site, `public/storage` exists.

---
Deeper shared-hosting extras (cron, SSL, email, permissions): [DEPLOYMENT_SHARED_HOSTING.md](DEPLOYMENT_SHARED_HOSTING.md)
