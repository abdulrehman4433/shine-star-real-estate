<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'Footer']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Footer Builder</div>
        <div class="ssm-page-header__subtitle">Configure footer columns, widgets, and the bottom bar.</div>
    </div>

    {{-- Footer Settings Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Footer Settings</h2>
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveFooterSettings" wire:loading.attr="disabled">
                    <i class="bi bi-check-lg"></i> Save Settings
                </button>
            </div>
            <form wire:submit="saveFooterSettings">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" wire:model="footerName" class="form-control @error('footerName') is-invalid @enderror" placeholder="Main Footer">
                        @error('footerName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="footerStatus" class="form-select @error('footerStatus') is-invalid @enderror">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="form-text">When inactive, the footer will not be displayed on the frontend.</div>
                        @error('footerStatus') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Columns</label>
                        <select wire:model.live="footerColumns" class="form-select @error('footerColumns') is-invalid @enderror">
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns (default)</option>
                            <option value="4">4 Columns</option>
                        </select>
                        <div class="form-text">Number of widget columns to display. Maximum 4.</div>
                        @error('footerColumns') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Widget Columns --}}
    <div class="row g-3">
        @for ($col = 0; $col < $footerColumns; $col++)
            <div class="col-md-{{ 12 / $footerColumns }}">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small">Column {{ $col + 1 }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="createWidget({{ $col }})" title="Add widget to column {{ $col + 1 }}">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <div class="card-body p-2">
                        @php $widgets = $widgetsByColumn->get($col, collect()); @endphp

                        @if ($widgets->isEmpty())
                            <p class="text-muted small p-2 mb-0 text-center">No widgets yet.</p>
                        @else
                            <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position)">
                                @foreach ($widgets as $widget)
                                    <li class="list-group-item px-2" x-sort:item="{{ $widget->id }}" wire:key="widget-{{ $widget->id }}">
                                        <div class="d-flex align-items-center gap-1">
                                            <span x-sort:handle class="small"><i class="bi bi-grip-vertical"></i></span>
                                            <span class="flex-grow-1 small {{ $widget->is_active ? '' : 'text-muted text-decoration-line-through' }}">
                                                {{ $widget->title ?: '('.ucfirst($widget->type).')' }}
                                            </span>
                                            <span class="badge {{ $widget->is_active ? 'text-bg-success' : 'text-bg-secondary' }}" style="font-size: 0.6rem;">
                                                {{ $widget->is_active ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                        <div class="btn-group btn-group-sm mt-1 w-100">
                                            <button type="button" class="btn btn-outline-secondary" wire:click="toggleActive({{ $widget->id }})" title="Toggle visibility">
                                                <i class="bi {{ $widget->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" wire:click="editWidget({{ $widget->id }})">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-outline-danger"
                                                @click="$store.confirm.open({ message: 'Delete \'{{ $widget->title ?: ucfirst($widget->type) }}\' widget?' }).then(ok => ok && $wire.deleteWidget({{ $widget->id }}))">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Bottom Bar Card --}}
    <div class="card mb-4 mt-4"
         x-data="{
             dragEl: null,
             moveTo(item, side) {
                 let prop = 'position' + item.charAt(0).toUpperCase() + item.slice(1).replace(/_([a-z])/g, (_, c) => c.toUpperCase());
                 this.$wire.set(prop, side);
             }
         }">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">Bottom Bar</h2>
                <button type="button" class="btn btn-sm btn-primary" wire:click="saveFooterSettings" wire:loading.attr="disabled" wire:target="saveFooterSettings">
                    <i class="bi bi-check-lg"></i> Save Bottom Bar
                </button>
            </div>
            <p class="small text-muted mb-3">Drag entire element cards between <strong>Left</strong> and <strong>Right</strong> columns. Toggle each element on/off with the switch, then click <strong>Save Bottom Bar</strong> — the on/off and drag actions above update this page instantly but are <strong>not</strong> saved to the live site until you click save.</p>

            @php
                $elements = [
                    ['key' => 'company_name', 'label' => 'Company Name', 'show' => 'showCompanyName', 'pos' => 'positionCompanyName'],
                    ['key' => 'tagline', 'label' => 'Tagline', 'show' => 'showTagline', 'pos' => 'positionTagline'],
                    ['key' => 'social_links', 'label' => 'Social Links', 'show' => 'showSocialLinks', 'pos' => 'positionSocialLinks'],
                    ['key' => 'copyright', 'label' => 'Copyright Text', 'show' => 'showCopyright', 'pos' => 'positionCopyright'],
                ];

                $leftEls = [];
                $rightEls = [];
                foreach ($elements as $e) {
                    $show = ${$e['show']};
                    $pos = ${$e['pos']};
                    if ($show) {
                        if ($pos === 'left') $leftEls[] = $e;
                        else $rightEls[] = $e;
                    }
                }
            @endphp

            <div class="row g-3">
                {{-- LEFT COLUMN --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <span class="fw-semibold small">Left Side</span>
                        </div>
                        <div class="card-body p-2 d-flex flex-column gap-2"
                             @dragover.prevent
                             @drop.prevent="if (dragEl) { moveTo(dragEl, 'left'); dragEl = null; }">

                            @forelse ($leftEls as $el)
                                @php $show = ${$el['show']}; $key = $el['key']; @endphp
                                <div draggable="true"
                                     @dragstart="dragEl = '{{ $key }}'"
                                     class="border rounded bg-white"
                                     style="cursor: grab;"
                                     wire:key="card-{{ $key }}">
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom bg-light">
                                        <span class="small fw-medium">
                                            <i class="bi bi-grip-vertical me-1 text-muted"></i>
                                            {{ $el['label'] }}
                                        </span>
                                        <div class="form-check form-switch mb-0">
                                            <input type="checkbox" wire:model.live="{{ $el['show'] }}" class="form-check-input" id="toggle-{{ $key }}">
                                            <label class="form-check-label small" for="toggle-{{ $key }}">{{ $show ? 'On' : 'Off' }}</label>
                                        </div>
                                    </div>
                                    <div class="p-2">
                                        @if ($key === 'company_name')
                                            <input type="text" wire:model="footerCompanyName" class="form-control form-control-sm" placeholder="Shine Star Marketing">

                                        @elseif ($key === 'tagline')
                                            <input type="text" wire:model="footerCopyrightTagline" class="form-control form-control-sm" placeholder="Find your next home...">

                                        @elseif ($key === 'social_links')
                                            @foreach ($footerSocialLinks as $i => $link)
                                                <div class="row g-1 mb-1 align-items-end" wire:key="social-link-{{ $i }}">
                                                    <div class="col-4">
                                                        <select wire:model="footerSocialLinks.{{ $i }}.platform" class="form-select form-select-sm">
                                                            <option value="">Platform</option>
                                                            <option value="facebook">Facebook</option>
                                                            <option value="twitter">X</option>
                                                            <option value="instagram">Instagram</option>
                                                            <option value="linkedin">LinkedIn</option>
                                                            <option value="youtube">YouTube</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" wire:model="footerSocialLinks.{{ $i }}.url" class="form-control form-control-sm" placeholder="URL">
                                                    </div>
                                                    <div class="col-2">
                                                        <input type="text" wire:model="footerSocialLinks.{{ $i }}.icon" class="form-control form-control-sm" placeholder="icon">
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:click="removeSocialLinkRow({{ $i }})" title="Remove"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" wire:click="addSocialLinkRow">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>

                                        @elseif ($key === 'copyright')
                                            <input type="text" wire:model="footerCopyrightText" class="form-control form-control-sm" placeholder="&copy; 2026...">
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small text-center py-3 mb-0">Drop elements here</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN --}}
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header text-md-end">
                            <span class="fw-semibold small">Right Side</span>
                        </div>
                        <div class="card-body p-2 d-flex flex-column gap-2"
                             @dragover.prevent
                             @drop.prevent="if (dragEl) { moveTo(dragEl, 'right'); dragEl = null; }">

                            @forelse ($rightEls as $el)
                                @php $show = ${$el['show']}; $key = $el['key']; @endphp
                                <div draggable="true"
                                     @dragstart="dragEl = '{{ $key }}'"
                                     class="border rounded bg-white"
                                     style="cursor: grab;"
                                     wire:key="card-r-{{ $key }}">
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom bg-light">
                                        <span class="small fw-medium">
                                            <i class="bi bi-grip-vertical me-1 text-muted"></i>
                                            {{ $el['label'] }}
                                        </span>
                                        <div class="form-check form-switch mb-0">
                                            <input type="checkbox" wire:model.live="{{ $el['show'] }}" class="form-check-input" id="toggle-r-{{ $key }}">
                                            <label class="form-check-label small" for="toggle-r-{{ $key }}">{{ $show ? 'On' : 'Off' }}</label>
                                        </div>
                                    </div>
                                    <div class="p-2">
                                        @if ($key === 'company_name')
                                            <input type="text" wire:model="footerCompanyName" class="form-control form-control-sm" placeholder="Shine Star Marketing">

                                        @elseif ($key === 'tagline')
                                            <input type="text" wire:model="footerCopyrightTagline" class="form-control form-control-sm" placeholder="Find your next home...">

                                        @elseif ($key === 'social_links')
                                            @foreach ($footerSocialLinks as $i => $link)
                                                <div class="row g-1 mb-1 align-items-end" wire:key="social-link-r-{{ $i }}">
                                                    <div class="col-4">
                                                        <select wire:model="footerSocialLinks.{{ $i }}.platform" class="form-select form-select-sm">
                                                            <option value="">Platform</option>
                                                            <option value="facebook">Facebook</option>
                                                            <option value="twitter">X</option>
                                                            <option value="instagram">Instagram</option>
                                                            <option value="linkedin">LinkedIn</option>
                                                            <option value="youtube">YouTube</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-5">
                                                        <input type="text" wire:model="footerSocialLinks.{{ $i }}.url" class="form-control form-control-sm" placeholder="URL">
                                                    </div>
                                                    <div class="col-2">
                                                        <input type="text" wire:model="footerSocialLinks.{{ $i }}.icon" class="form-control form-control-sm" placeholder="icon">
                                                    </div>
                                                    <div class="col-1">
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" wire:click="removeSocialLinkRow({{ $i }})" title="Remove"><i class="bi bi-trash"></i></button>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" wire:click="addSocialLinkRow">
                                                <i class="bi bi-plus-lg"></i> Add
                                            </button>

                                        @elseif ($key === 'copyright')
                                            <input type="text" wire:model="footerCopyrightText" class="form-control form-control-sm" placeholder="&copy; 2026...">
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small text-center py-3 mb-0">Drop elements here</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Widget Form Modal --}}
    @if ($showForm)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="closeForm">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="saveWidget">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingId ? 'Edit Widget' : 'Add Widget' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeForm"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Title (optional)</label>
                                <input type="text" wire:model="title" class="form-control">
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Type</label>
                                    <select wire:model.live="type" class="form-select">
                                        @foreach ($types as $t)
                                            <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Column</label>
                                    <select wire:model="column" class="form-select">
                                        @for ($i = 0; $i < $footerColumns; $i++)
                                            <option value="{{ $i }}">Column {{ $i + 1 }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            @if ($type === 'text')
                                <div class="mb-3">
                                    <label class="form-label">Body</label>
                                    <textarea wire:model="body" rows="4" class="form-control"></textarea>
                                </div>
                            @else
                                <label class="form-label">Links</label>
                                @foreach ($links as $index => $link)
                                    <div class="row g-2 mb-2">
                                        <div class="col-5">
                                            <input type="text" placeholder="Label" wire:model="links.{{ $index }}.label" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-5">
                                            <input type="text" placeholder="URL" wire:model="links.{{ $index }}.url" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100" wire:click="removeLinkRow({{ $index }})"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addLinkRow"><i class="bi bi-plus-lg"></i> Add Link</button>
                            @endif

                            <div class="form-check form-switch mt-3">
                                <input type="checkbox" wire:model="isActive" class="form-check-input" id="widget-active">
                                <label class="form-check-label" for="widget-active">Visible</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeForm">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveWidget">
                                <span wire:loading wire:target="saveWidget" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
