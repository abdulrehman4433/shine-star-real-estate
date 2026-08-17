<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Settings', 'route' => 'admin.settings.index'],
        ['label' => 'SEO Settings'],
    ]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">SEO Settings</div>
        <div class="ssm-page-header__subtitle">Default meta tags, robots.txt, analytics, and the sitemap.</div>
    </div>

    <form wire:submit="save">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-tags"></i> Default Meta</h2>
                <p class="text-muted small">Used as a fallback for any page/property/post that doesn't have its own SEO fields filled in.</p>

                <div class="mb-3">
                    <label class="form-label">Default meta title</label>
                    <input type="text" wire:model="defaultMetaTitle" class="form-control @error('defaultMetaTitle') is-invalid @enderror">
                    @error('defaultMetaTitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Default meta description</label>
                    <textarea wire:model="defaultMetaDescription" rows="2" class="form-control @error('defaultMetaDescription') is-invalid @enderror"></textarea>
                    @error('defaultMetaDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-robot"></i> robots.txt</h2>
                <p class="text-muted small">Served live at <code>/robots.txt</code> — changes here take effect immediately.</p>

                <textarea wire:model="robotsTxt" rows="6" class="form-control font-monospace @error('robotsTxt') is-invalid @enderror"></textarea>
                @error('robotsTxt') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-graph-up"></i> Analytics & Search Console</h2>

                <div class="mb-3">
                    <label class="form-label">Google Analytics measurement ID</label>
                    <input type="text" wire:model="gaMeasurementId" placeholder="G-XXXXXXXXXX" class="form-control @error('gaMeasurementId') is-invalid @enderror">
                    @error('gaMeasurementId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Free — create one at <code>analytics.google.com</code>.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Google Search Console verification content</label>
                    <input type="text" wire:model="gscVerification" placeholder="Paste just the 'content' value from the meta tag Google gives you" class="form-control @error('gscVerification') is-invalid @enderror">
                    @error('gscVerification') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Free — verify ownership at <code>search.google.com/search-console</code>.</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
            Save Settings
        </button>
    </form>

    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h6 ssm-form-section-title"><i class="bi bi-diagram-3"></i> Sitemap</h2>
            <p class="text-muted small mb-2">
                <code>sitemap.xml</code> regenerates automatically whenever a property, page, or blog post is
                saved, published, or deleted. Use this button to force an immediate regeneration.
            </p>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="regenerateSitemap">
                <i class="bi bi-arrow-clockwise"></i> Regenerate Now
            </button>
            <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up-right"></i> View sitemap.xml</a>
        </div>
    </div>
</div>
