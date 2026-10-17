# Changes — Version 1 (Project Blocks: per-size payment + media gallery)

Date: 2026-10-15
Purpose: everything needed to update a **live server** with the Project Blocks redesign —
per-size dynamic payment options, block media gallery (Images / Videos / Socity Map tabs),
video files + video links (YouTube/Vimeo) per block.

---

## How to update the live server (4 steps)

1. **Upload / replace files** — see *Changed files* and *New files* below; delete the *Deleted files*.
2. **Apply the database changes** — see *Database changes* below (Option A: `php artisan migrate`,
   recommended because it also moves your existing data · Option B: raw MySQL for phpMyAdmin hosts).
3. **Mark migrations as run** (only if you used Option B) — otherwise a later `artisan migrate`
   would try to run them again.
4. **Clear caches** — see *After upload* below.

---

## 1. Database changes (run in this order)

### Migration 1 — `2026_10_14_120000_add_video_urls_to_projects_table`
File (NEW): `database/migrations/2026_10_14_120000_add_video_urls_to_projects_table.php`

Adds a multi-video JSON column to `projects` and backfills it from the old single `video_url`.

```sql
ALTER TABLE `projects` ADD `video_urls` JSON NULL AFTER `video_url`;

-- Backfill: old single link becomes the first entry (only touches rows without video_urls)
UPDATE `projects` SET `video_urls` = JSON_ARRAY(`video_url`)
 WHERE `video_url` IS NOT NULL AND `video_urls` IS NULL;
```

### Migration 2 — `2026_10_14_140000_restructure_project_blocks_to_json`
File (NEW): `database/migrations/2026_10_14_140000_restructure_project_blocks_to_json.php`

Gives each block two JSON columns (`plot_sizes`, `payment_options`) and drops the old
`project_plot_sizes` table. **Each old plot-size row is moved into its block's `plot_sizes`
JSON** (descriptive fields only: size_value, unit, category, bedrooms, bathrooms — the old
per-plot payment numbers were intentionally discarded by design).

```sql
ALTER TABLE `project_blocks`
  ADD `payment_options` JSON NULL AFTER `description`,
  ADD `plot_sizes` JSON NULL AFTER `payment_options`;

DROP TABLE `project_plot_sizes`;
```

> ⚠️ The data move (rows of `project_plot_sizes` → block JSON) is done by the migration
> script itself. **If your live `project_plot_sizes` table still has rows you want to keep,
> use Option A (`php artisan migrate`) for this step** — running the raw SQL above would
> drop those rows. Only run the raw SQL if that data can be discarded.

### Migration 3 — `2026_10_15_000000_relink_block_payment_to_sizes_and_add_video_urls`
File (NEW): `database/migrations/2026_10_15_000000_relink_block_payment_to_sizes_and_add_video_urls.php`

Moves each block's payment key/value rows onto its **first size option** (payment is per-size
now), adds `video_urls` to `project_blocks`, and drops the block-level `payment_options` column.

```sql
ALTER TABLE `project_blocks` ADD `video_urls` JSON NULL AFTER `plot_sizes`;

-- Move each block's payment KV onto its FIRST size option (MySQL 8 / MariaDB JSON functions)
UPDATE `project_blocks`
   SET `plot_sizes` = JSON_SET(`plot_sizes`, '$[0].payment_options', `payment_options`)
 WHERE `payment_options` IS NOT NULL
   AND `plot_sizes` IS NOT NULL
   AND JSON_LENGTH(`plot_sizes`) > 0
   AND JSON_LENGTH(`payment_options`) > 0;

ALTER TABLE `project_blocks` DROP `payment_options`;
```

### Final table shapes

```
projects         : … existing columns …, video_url, video_urls JSON
project_blocks   : id, project_id, name, description, plot_sizes JSON, video_urls JSON, order, timestamps
project_plot_sizes: DROPPED (no longer exists)
```

`plot_sizes` JSON row shape:
`{"size_value": 5, "unit": "marla", "category": "Residential Plot", "bedrooms": null, "bathrooms": null, "payment_options": [{"key": "Total Rate", "value": "2,300,000"}, …]}`

### Option A (recommended) — artisan

Upload the 3 migration files, then on the server:

```
php artisan migrate
```

It runs all three in order and moves your data automatically.

### Option B — raw MySQL, then mark as run

If you applied the SQL above manually, insert the bookkeeping rows so a future
`php artisan migrate` does not re-run them:

```sql
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2026_10_14_120000_add_video_urls_to_projects_table', (SELECT COALESCE(MAX(m),0)+1 FROM (SELECT MAX(`batch`) m FROM `migrations`) t)),
('2026_10_14_140000_restructure_project_blocks_to_json', (SELECT COALESCE(MAX(m),0)+1 FROM (SELECT MAX(`batch`) m FROM `migrations`) t)),
('2026_10_15_000000_relink_block_payment_to_sizes_and_add_video_urls', (SELECT COALESCE(MAX(m),0)+1 FROM (SELECT MAX(`batch`) m FROM `migrations`) t));
```

> All SQL in this file was executed and verified on a MySQL-compatible engine (MariaDB 12.3)
> against real data before being written down here.

---

## 2. Changed files (replace on the server)

| File | What changed |
|---|---|
| `app/Http/Controllers/Frontend/HomeController.php` | blocks count fix (JSON sizes) |
| `app/Http/Controllers/Frontend/ProjectsController.php` | eager-loads `blocks.media` for the project page |
| `app/Livewire/Admin/Projects/Form.php` | per-size payment KV rows, block video-link rows, block media upload/remove, upsert save |
| `app/Livewire/Admin/Projects/Manager.php` | blocks count fix |
| `app/Livewire/Admin/Projects/Show.php` | loads per-block media for the admin show page |
| `app/Models/Project.php` | deletes block media on project delete; video-links/embed URL helpers |
| `app/Models/ProjectBlock.php` | JSON `plot_sizes` + `video_urls`, per-size payment helpers, media collections + thumb conversion |
| `config/livewire.php` | temporary-upload ceiling raised to 100 MB (so 50 MB video uploads fit) |
| `database/seeders/ProjectSeeder.php` | demo data now seeds per-size payment rows (**demo only — do not seed the live DB**) |
| `database/seeders/RealProjectSeeder.php` | same (**re-running this DELETES and recreates seeded blocks incl. their uploaded media — never run it on live**) |
| `resources/views/frontend/projects/show.blade.php` | payment-plan matrix table (Size, Category, terms), gallery tabs with 100×100 thumbs + modals, Videos accordion (open by default, 500px + scroll) |
| `resources/views/livewire/admin/projects/form.blade.php` | per-size payment editor, block media + video-link editors |
| `resources/views/livewire/admin/projects/show.blade.php` | per-size payment table, block media + video links display |

## 3. New files (upload)

- `database/migrations/2026_10_14_120000_add_video_urls_to_projects_table.php`
- `database/migrations/2026_10_14_140000_restructure_project_blocks_to_json.php`
- `database/migrations/2026_10_15_000000_relink_block_payment_to_sizes_and_add_video_urls.php`
- `docs/changes-version-1.md` (this file)

## 4. Deleted files (remove on the server)

- `app/Models/ProjectPlotSize.php` — its table was dropped; the model is no longer used anywhere.

---

## 5. After upload — clear caches

```
php artisan config:clear && php artisan view:clear && php artisan cache:clear && php artisan optimize
```

Shared hosting (no artisan): delete the **contents** of `bootstrap/cache/` and
`storage/framework/views/` (keep the folders and their `.gitignore` files), then re-create
the storage symlink if media links 404 (`php artisan storage:link`).

---

## 6. Notes & gotchas

- **Block media** (block images / videos / PDFs) is stored via Spatie in the `media` table
  (schema unchanged) with files under `storage/app/public/…` — if you copy `storage/` between
  servers, block media comes along.
- **Old per-plot payment numbers are intentionally gone** (clean-slate decision). Payment is
  entered per size option in the admin, as dynamic key/value rows — they display in the
  frontend payment-plan table (Merging Limit first … Total Rate/Total Price last).
- **Do not run any seeder on live.** `ProjectSeeder` skips when projects exist, but
  `RealProjectSeeder` rebuilds seeded blocks and would delete their uploaded media files.
- The root `shine_star_marketing.sql` dump predates this schema — regenerate your backup
  **after** this update.
