<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'CDN Assets']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">CDN Assets</div>
        <div class="ssm-page-header__subtitle">
            Manage external CSS, JavaScript, and font CDN links that are dynamically loaded on the frontend.
            Header assets are loaded in the <code>&lt;head&gt;</code>, footer assets are loaded before <code>&lt;/body&gt;</code>.
        </div>
    </div>

    {{-- Header Assets Section --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-heading"></i> Header Assets</span>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="createAsset('header')">
                <i class="bi bi-plus-lg"></i> Add
            </button>
        </div>
        <div class="card-body p-2">
            @if ($headerAssets->isEmpty())
                <div class="ssm-empty-state py-4">
                    <i class="bi bi-heading"></i>
                    <p class="small">No header assets yet.</p>
                </div>
            @else
                <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position, 'header')">
                    @foreach ($headerAssets as $asset)
                        <li class="list-group-item px-3 d-flex align-items-center gap-2"
                            x-sort:item="{{ $asset->id }}" wire:key="header-asset-{{ $asset->id }}">
                            <span x-sort:handle><i class="bi bi-grip-vertical"></i></span>

                            <span class="flex-grow-1 small {{ $asset->is_active ? '' : 'text-muted text-decoration-line-through' }}">
                                <span class="badge {{ $asset->type === 'css' ? 'text-bg-info' : ($asset->type === 'js' ? 'text-bg-warning' : 'text-bg-secondary') }} me-1" style="font-size: 0.65rem;">
                                    {{ strtoupper($asset->type) }}
                                </span>
                                <code class="small">{{ \Illuminate\Support\Str::limit($asset->url, 60) }}</code>
                            </span>

                            @unless ($asset->is_active)
                                <span class="badge text-bg-secondary" style="font-size: 0.6rem;">Inactive</span>
                            @endunless

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" wire:click="toggleActive({{ $asset->id }})" title="Toggle active">
                                    <i class="bi {{ $asset->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary" wire:click="editAsset({{ $asset->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete this CDN asset?' }).then(ok => ok && $wire.delete({{ $asset->id }}))">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Footer Assets Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-window-stack"></i> Footer Assets</span>
            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="createAsset('footer')">
                <i class="bi bi-plus-lg"></i> Add
            </button>
        </div>
        <div class="card-body p-2">
            @if ($footerAssets->isEmpty())
                <div class="ssm-empty-state py-4">
                    <i class="bi bi-window-stack"></i>
                    <p class="small">No footer assets yet.</p>
                </div>
            @else
                <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position, 'footer')">
                    @foreach ($footerAssets as $asset)
                        <li class="list-group-item px-3 d-flex align-items-center gap-2"
                            x-sort:item="{{ $asset->id }}" wire:key="footer-asset-{{ $asset->id }}">
                            <span x-sort:handle><i class="bi bi-grip-vertical"></i></span>

                            <span class="flex-grow-1 small {{ $asset->is_active ? '' : 'text-muted text-decoration-line-through' }}">
                                <span class="badge {{ $asset->type === 'css' ? 'text-bg-info' : ($asset->type === 'js' ? 'text-bg-warning' : 'text-bg-secondary') }} me-1" style="font-size: 0.65rem;">
                                    {{ strtoupper($asset->type) }}
                                </span>
                                <code class="small">{{ \Illuminate\Support\Str::limit($asset->url, 60) }}</code>
                            </span>

                            @unless ($asset->is_active)
                                <span class="badge text-bg-secondary" style="font-size: 0.6rem;">Inactive</span>
                            @endunless

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" wire:click="toggleActive({{ $asset->id }})" title="Toggle active">
                                    <i class="bi {{ $asset->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary" wire:click="editAsset({{ $asset->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete this CDN asset?' }).then(ok => ok && $wire.delete({{ $asset->id }}))">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    @if ($showModal)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="closeModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingId ? 'Edit CDN Asset' : 'Add CDN Asset' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select wire:model="location" class="form-select">
                                    <option value="header">Header (&lt;head&gt;)</option>
                                    <option value="footer">Footer (before &lt;/body&gt;)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select wire:model="type" class="form-select">
                                    <option value="css">CSS Stylesheet</option>
                                    <option value="js">JavaScript</option>
                                    <option value="font">Font (link or @import)</option>
                                </select>
                                <div class="form-text small">Choose the type of asset. Fonts loaded in the head, JS in the footer.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL</label>
                                <input type="text" wire:model="url" class="form-control @error('url') is-invalid @enderror"
                                    placeholder="https://cdn.example.com/style.css">
                                @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text small">
                                    Paste a plain URL only — not a full <code>&lt;link&gt;</code> or <code>&lt;script&gt;</code> tag.
                                    The correct tag is generated automatically based on the type you select above.
                                </div>
                            </div>

                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="isActive" class="form-check-input" id="cdn-asset-active">
                                <label class="form-check-label" for="cdn-asset-active">Active</label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
