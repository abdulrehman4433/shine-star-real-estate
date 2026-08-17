<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Settings', 'route' => 'admin.settings.index'],
        ['label' => 'Backup'],
    ]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Backup &amp; Migration</div>
        <div class="ssm-page-header__subtitle">Everything needed to move this site to a new server — the database and every uploaded file.</div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle-fill me-1"></i>
        Moving to a new system takes <strong>three</strong> things: the project files (copy the whole folder,
        or re-deploy from your repository), this <strong>database backup</strong>, and this
        <strong>media files backup</strong>. The database only stores file <em>paths</em> — the actual
        images/PDFs live on disk, so both downloads below are required, not just one.
        See <code>MIGRATION_GUIDE.md</code> in the project root for the full step-by-step, and
        <a href="{{ route('admin.settings.system-check') }}">System Requirements Check</a> to confirm the new
        server is ready before you import anything.
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-database-fill-down"></i> Database Backup</h2>
                    <p class="text-muted small">
                        A complete, plain SQL dump of every table — pages, header/menu/footer content, CDN
                        links, properties, projects, blog posts, reviews, settings, users, chat history,
                        everything. Restoring it recreates the database exactly as it is right now.
                    </p>
                    <ul class="text-muted small mb-4">
                        <li>No dependency on the <code>mysqldump</code> command being installed — works from PHP alone.</li>
                        <li>Import on the new server with: <code>mysql -u USER -p DATABASE_NAME &lt; this_file.sql</code></li>
                        <li>Or upload it through phpMyAdmin's "Import" tab.</li>
                    </ul>
                    <div class="mt-auto">
                        <button type="button" wire:click="downloadDatabase" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="downloadDatabase"><i class="bi bi-download"></i> Download Database Backup</span>
                            <span wire:loading wire:target="downloadDatabase">Generating&hellip;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-file-earmark-zip-fill"></i> Media Files Backup</h2>
                    <p class="text-muted small">
                        A zip of every uploaded file — property/project photos, brochures, review photos,
                        the site logo, blog images, chat attachments. Extract it into <code>storage/app/public</code>
                        on the new server (matching the same folder structure) before running
                        <code>php artisan storage:link</code>.
                    </p>
                    <p class="text-muted small mb-4">
                        Current size: <strong>{{ $this->mediaSizeLabel() }}</strong>
                    </p>
                    <div class="mt-auto">
                        <button type="button" wire:click="downloadMedia" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="downloadMedia"><i class="bi bi-download"></i> Download Media Files Backup</span>
                            <span wire:loading wire:target="downloadMedia">Zipping&hellip;</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h6 ssm-form-section-title"><i class="bi bi-list-check"></i> Moving to a new server — quick checklist</h2>
            <ol class="text-muted small mb-0">
                <li>Copy the project files to the new server (or clone/pull the repository there).</li>
                <li>Run <a href="{{ route('admin.settings.system-check') }}">System Requirements Check</a> on the new server first — fix anything it flags before continuing.</li>
                <li>Create a new, empty database and import the <strong>Database Backup</strong> .sql file into it.</li>
                <li>Extract the <strong>Media Files Backup</strong> zip into <code>storage/app/public</code>.</li>
                <li>Copy <code>.env</code> (or recreate it) with the new server's database credentials, <code>APP_URL</code>, and mail/broadcast settings.</li>
                <li>Run <code>composer install</code>, <code>npm install &amp;&amp; npm run build</code>, <code>php artisan storage:link</code>, and <code>php artisan migrate --force</code> (safe to run even though the data is already imported — it just confirms nothing is pending).</li>
                <li>If using live chat, start Reverb: <code>php artisan reverb:start</code> (or supervise it as a service).</li>
            </ol>
        </div>
    </div>
</div>
