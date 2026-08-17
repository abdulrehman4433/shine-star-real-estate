<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'Menus']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Menus</div>
            <div class="ssm-page-header__subtitle">Build the header and footer navigation.</div>
        </div>
        <button type="button" class="btn btn-primary btn-sm" wire:click="createMenu"><i class="bi bi-plus-lg"></i> Add Menu</button>
    </div>

    <ul class="nav nav-tabs mb-4">
        @foreach ($menus as $menu)
            <li class="nav-item">
                <button type="button" wire:click="selectMenu({{ $menu->id }})"
                    class="nav-link {{ $activeMenu && $activeMenu->id === $menu->id ? 'active' : '' }}">
                    {{ $menu->name }}
                    <span class="badge text-bg-light text-muted">{{ ucfirst($menu->location) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    @if (! $activeMenu)
        <div class="alert alert-info">No menus yet. Click "Add Menu" to create one.</div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                    <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-list-ul"></i> Items in "{{ $activeMenu->name }}"</h2>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" wire:click="createItem"><i class="bi bi-plus-lg"></i> Add Item</button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                            @click="$store.confirm.open({ message: 'Delete the entire \'{{ $activeMenu->name }}\' menu and all its items?' }).then(ok => ok && $wire.deleteMenu({{ $activeMenu->id }}))">
                            <i class="bi bi-trash"></i> Delete Menu
                        </button>
                    </div>
                </div>

                @if (empty($tree))
                    <div class="ssm-empty-state">
                        <i class="bi bi-list-ul"></i>
                        <p>No items yet. Click "Add Item" to build this menu.</p>
                    </div>
                @else
                    <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position)">
                        @foreach ($tree as $node)
                            @include('livewire.admin.menus.partials.tree', ['node' => $node])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    @if ($showMenuForm)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="closeMenuForm">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="saveMenu">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Menu</h5>
                            <button type="button" class="btn-close" wire:click="closeMenuForm"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" wire:model="menuName" class="form-control @error('menuName') is-invalid @enderror">
                                @error('menuName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select wire:model="menuLocation" class="form-select">
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->value }}">{{ $location->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeMenuForm">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveMenu">
                                <span wire:loading wire:target="saveMenu" class="spinner-border spinner-border-sm me-2"></span>
                                Create
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showItemForm)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="closeItemForm">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="saveItem">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingItemId ? 'Edit Item' : 'Add Item' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeItemForm"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" wire:model="label" class="form-control @error('label') is-invalid @enderror">
                                @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Link to page (optional)</label>
                                <select wire:model="pageId" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}">{{ $page->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Or a custom URL</label>
                                <input type="text" wire:model="url" placeholder="/properties or https://..." class="form-control @error('url') is-invalid @enderror">
                                @error('url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">If both a page and a URL are set, the page link wins.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parent item (for dropdowns)</label>
                                <select wire:model="parentId" class="form-select @error('parentId') is-invalid @enderror">
                                    <option value="">— None (top level) —</option>
                                    @foreach ($itemOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->label }}</option>
                                    @endforeach
                                </select>
                                @error('parentId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Open in</label>
                                    <select wire:model="target" class="form-select">
                                        <option value="_self">Same tab</option>
                                        <option value="_blank">New tab</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" wire:model="isActive" class="form-check-input" id="item-active">
                                        <label class="form-check-label" for="item-active">Visible</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeItemForm">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveItem">
                                <span wire:loading wire:target="saveItem" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
