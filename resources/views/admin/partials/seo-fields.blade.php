<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6">SEO</h2>

        <div class="mb-3">
            <label class="form-label">Meta title</label>
            <input type="text" wire:model="seoMetaTitle" class="form-control @error('seoMetaTitle') is-invalid @enderror" placeholder="Defaults to the title if left blank">
            @error('seoMetaTitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Meta description</label>
            <textarea wire:model="seoMetaDescription" rows="2" maxlength="255" class="form-control @error('seoMetaDescription') is-invalid @enderror" placeholder="Defaults to an auto-generated summary if left blank"></textarea>
            @error('seoMetaDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Meta keywords</label>
                <input type="text" wire:model="seoMetaKeywords" placeholder="comma, separated, keywords" class="form-control @error('seoMetaKeywords') is-invalid @enderror">
                @error('seoMetaKeywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Canonical URL</label>
                <input type="text" wire:model="seoCanonicalUrl" placeholder="Defaults to this page's own URL" class="form-control @error('seoCanonicalUrl') is-invalid @enderror">
                @error('seoCanonicalUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>
