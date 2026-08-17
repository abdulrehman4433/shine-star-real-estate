<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'Header']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Edit Header</div>
        <div class="ssm-page-header__subtitle">Customize the site header's HTML, CSS, and behavior.</div>
    </div>

    {{-- Header Settings Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-gear"></i> Header Settings</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" wire:click="previewTemplate"
                        wire:loading.attr="disabled">
                        <i class="bi bi-box-arrow-up-right"></i> Preview
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="saveDraft"
                        wire:loading.attr="disabled">
                        <i class="bi bi-cloud"></i> Save Draft
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" wire:click="publish"
                        wire:loading.attr="disabled">
                        <i class="bi bi-upload"></i> Save &amp; Publish
                    </button>
                </div>
            </div>

            <form wire:submit="save">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
            </form>
        </div>
    </div>

    {{-- Code Editor Section --}}
    <div class="ssm-header-editor" wire:key="header-editor-{{ $editingId ?? 'new' }}">
        {{-- Code Editor Tabs --}}
        <div class="ssm-header-editor__tabs">
            <button type="button" class="ssm-header-editor__tab {{ $activeEditorTab === 'html' ? 'is-active' : '' }}"
                wire:click="switchEditorTab('html')">
                <i class="bi bi-code"></i> HTML
            </button>
            <button type="button" class="ssm-header-editor__tab {{ $activeEditorTab === 'css' ? 'is-active' : '' }}"
                wire:click="switchEditorTab('css')">
                <i class="bi bi-palette"></i> CSS
            </button>
            <button type="button" class="ssm-header-editor__tab {{ $activeEditorTab === 'js' ? 'is-active' : '' }}"
                wire:click="switchEditorTab('js')">
                <i class="bi bi-filetype-js"></i> JS
            </button>
            <button type="button" class="ssm-header-editor__tab {{ $activeEditorTab === 'settings' ? 'is-active' : '' }}"
                wire:click="switchEditorTab('settings')">
                <i class="bi bi-gear"></i> Settings
            </button>
        </div>

        {{-- Editor Content --}}
        <div class="ssm-header-editor__content"
             x-data="codeMirrorEditors()"
             x-init="initEditors()"
             wire:key="code-editors-{{ $editingId ?? 'new' }}">
            {{-- HTML Editor --}}
            <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'html'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 small fw-semibold">
                        <i class="bi bi-code"></i> HTML Template
                    </label>
                    <span class="text-muted small">Use variables: <code>@{{ $siteName }}</code> <code>@{!! $menuHtml !!}</code></span>
                </div>
                <div class="ssm-cm-wrapper">
                    <textarea x-ref="htmlEditor"
                        wire:model="html"
                        class="form-control ssm-header-editor__textarea"
                        rows="28"
                        spellcheck="false"
                        placeholder="&lt;header&gt;...&lt;/header&gt;"></textarea>
                </div>
            </div>

            {{-- CSS Editor --}}
            <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'css'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 small fw-semibold">
                        <i class="bi bi-palette"></i> CSS Styles
                    </label>
                    <span class="text-muted small">Use settings variables like <code>@{{ $headerBgColor }}</code></span>
                </div>
                <div class="ssm-cm-wrapper">
                    <textarea x-ref="cssEditor"
                        wire:model="css"
                        class="form-control ssm-header-editor__textarea"
                        rows="28"
                        spellcheck="false"
                        placeholder="/* Header styles */"></textarea>
                </div>
            </div>

            {{-- JS Editor --}}
            <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'js'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 small fw-semibold">
                        <i class="bi bi-filetype-js"></i> JavaScript
                    </label>
                    <span class="text-muted small">Sticky header, mobile toggle, dropdowns</span>
                </div>
                <div class="ssm-cm-wrapper">
                    <textarea x-ref="jsEditor"
                        wire:model="js"
                        class="form-control ssm-header-editor__textarea"
                        rows="28"
                        spellcheck="false"
                        placeholder="// Custom header JS"></textarea>
                </div>
            </div>

            {{-- Settings Panel --}}
            <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'settings'">
                <div class="ssm-header-editor__settings">
                    <div class="row g-3">
                        {{-- Layout --}}
                        <div class="col-12">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-layout-three-columns me-1"></i> Layout
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Header Height (px)</label>
                            <input type="number" wire:model="headerHeight" class="form-control form-control-sm" min="50" max="200">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Menu Position</label>
                            <select wire:model="menuPosition" class="form-select form-select-sm">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="sticky" class="form-check-input" id="setting-sticky">
                                <label class="form-check-label small" for="setting-sticky">Sticky Header</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="transparent" class="form-check-input" id="setting-transparent">
                                <label class="form-check-label small" for="setting-transparent">Transparent</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="shadowEnabled" class="form-check-input" id="setting-shadow">
                                <label class="form-check-label small" for="setting-shadow">Shadow</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="showSearch" class="form-check-input" id="setting-search">
                                <label class="form-check-label small" for="setting-search">Search Icon</label>
                            </div>
                        </div>

                        {{-- Colors --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-palette me-1"></i> Colors
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Background</label>
                            <div class="d-flex gap-2">
                                <input type="color" wire:model="headerBgColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                <input type="text" wire:model="headerBgColor" class="form-control form-control-sm" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Text Color</label>
                            <div class="d-flex gap-2">
                                <input type="color" wire:model="headerTextColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                <input type="text" wire:model="headerTextColor" class="form-control form-control-sm" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Border Color</label>
                            <div class="d-flex gap-2">
                                <input type="color" wire:model="headerBorderColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                <input type="text" wire:model="headerBorderColor" class="form-control form-control-sm" maxlength="7">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Shadow Color</label>
                            <input type="text" wire:model="shadowColor" class="form-control form-control-sm" placeholder="rgba(0,0,0,0.08)">
                        </div>

                        {{-- Typography --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-fonts me-1"></i> Typography
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Font Family</label>
                            <input type="text" wire:model="fontFamily" class="form-control form-control-sm" placeholder="Inter, sans-serif">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Nav Font Size (px)</label>
                            <input type="number" wire:model="navFontSize" class="form-control form-control-sm" min="10" max="24">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Font Weight</label>
                            <input type="number" wire:model="navFontWeight" class="form-control form-control-sm" min="300" max="900" step="100">
                        </div>

                        {{-- Spacing --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-arrows-expand me-1"></i> Spacing &amp; Shape
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Nav Item Padding X</label>
                            <input type="number" wire:model="navItemPaddingX" class="form-control form-control-sm" min="0" max="40">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Nav Item Padding Y</label>
                            <input type="number" wire:model="navItemPaddingY" class="form-control form-control-sm" min="0" max="40">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Border Radius (px)</label>
                            <input type="number" wire:model="borderRadius" class="form-control form-control-sm" min="0" max="30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Mobile Breakpoint (px)</label>
                            <input type="number" wire:model="mobileBreakpoint" class="form-control form-control-sm" min="576" max="1400">
                        </div>

                        {{-- Logo --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-image me-1"></i> Logo
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Logo Height (px)</label>
                            <input type="number" wire:model="logoHeight" class="form-control form-control-sm" min="20" max="120">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Logo Width (px)</label>
                            <input type="number" wire:model="logoWidth" class="form-control form-control-sm" min="40" max="300">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Custom Logo</label>
                            <input type="file" wire:model="logoUpload" class="form-control form-control-sm" accept="image/*">
                            @if ($editingId)
                                @php $template = \App\Models\HeaderTemplate::find($editingId); @endphp
                                @if ($template && $template->logoUrl())
                                    <div class="mt-1 d-flex align-items-center gap-2">
                                        <img src="{{ $template->logoUrl() }}" alt="Logo" style="height:24px;width:auto;">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0" wire:click="removeLogo">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- CTA Buttons --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-bullseye me-1"></i> CTA Buttons
                            </h6>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="showCta" class="form-check-input" id="setting-cta">
                                <label class="form-check-label small" for="setting-cta">Show CTA Buttons</label>
                            </div>
                        </div>
                        @if ($showCta)
                            <div class="col-md-4">
                                <label class="form-label small">Login Label</label>
                                <input type="text" wire:model="ctaLoginLabel" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Register Label</label>
                                <input type="text" wire:model="ctaRegisterLabel" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Contact Label</label>
                                <input type="text" wire:model="ctaContactLabel" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Contact URL</label>
                                <input type="text" wire:model="ctaContactUrl" class="form-control form-control-sm">
                            </div>
                        @endif

                        {{-- Dropdown --}}
                        <div class="col-12 mt-3">
                            <h6 class="fw-bold text-primary border-bottom pb-2">
                                <i class="bi bi-layers me-1"></i> Dropdown Style
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Hover Animation</label>
                            <select wire:model="dropdownHoverStyle" class="form-select form-select-sm">
                                <option value="fade">Fade In</option>
                                <option value="slide">Slide Down</option>
                                <option value="scale">Scale Up</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div wire:loading class="position-fixed bottom-0 end-0 m-4" style="z-index: 9999;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

{{-- Editor Styles & CodeMirror Initialization --}}
@push('styles')
<style>
    .ssm-header-editor__textarea {
        font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        tab-size: 4;
        resize: vertical;
        background: #1a1b26;
        color: #c9d1d9;
        border: 1px solid #30363d;
        border-radius: 8px;
        padding: 16px;
    }

    .ssm-header-editor__textarea:focus {
        border-color: var(--ssm-royal-blue, #083F7F);
        box-shadow: 0 0 0 2px rgba(8, 63, 127, 0.2);
        background: #16171f;
        color: #e6edf3;
    }

    .ssm-header-editor__textarea::placeholder {
        color: #484f58;
    }

    .ssm-cm-wrapper .CodeMirror {
        height: auto;
        min-height: 500px;
        font-size: 13px;
        border-radius: 8px;
        font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    }

    .ssm-cm-wrapper .CodeMirror-scroll {
        min-height: 500px;
    }

    .ssm-cm-wrapper .CodeMirror-gutters {
        border-right: 1px solid #30363d;
        background: #16171f;
    }

    .ssm-cm-wrapper .CodeMirror-linenumber {
        color: #484f58;
    }

    .ssm-cm-wrapper .cm-s-material-darker .CodeMirror-cursor {
        border-left: 2px solid #FBAB03;
    }

    .ssm-cm-wrapper .CodeMirror-activeline-background {
        background: rgba(8, 63, 127, 0.08);
    }

    /* Editor tabs styling */
    .ssm-header-editor__tabs {
        display: flex;
        gap: 2px;
        background: #1a1b26;
        padding: 8px 8px 0;
        border-radius: 8px 8px 0 0;
    }

    .ssm-header-editor__tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #8b949e;
        background: transparent;
        border: none;
        border-radius: 6px 6px 0 0;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .ssm-header-editor__tab:hover {
        color: #c9d1d9;
        background: rgba(255,255,255,0.06);
    }

    .ssm-header-editor__tab.is-active {
        color: #e6edf3;
        background: #16171f;
    }

    .ssm-header-editor__content {
        background: #16171f;
        border-radius: 0 0 8px 8px;
    }

    .ssm-header-editor__code-area {
        padding: 16px;
    }

    /* Settings panel - light background matching admin page, no dark edges */
    .ssm-header-editor__code-area:has(.ssm-header-editor__settings) {
        background: #ffffff;
        border: none;
        border-radius: 0 0 8px 8px;
    }

    .ssm-header-editor__settings {
        background: #ffffff;
        border: none;
        border-radius: 0 0 8px 8px;
        padding: 1.5rem;
    }

    .ssm-cm-wrapper .CodeMirror {
        height: auto;
        min-height: 500px;
        font-size: 13px;
        border-radius: 8px;
        font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace;
    }
</style>
@endpush

@push('scripts')
<script>
    function codeMirrorEditors() {
        return {
            editors: [],
            initEditors() {
                var self = this;
                this.$nextTick(() => {
                    self.setupEditor('htmlEditor', 'htmlmixed');
                    self.setupEditor('cssEditor', 'css');
                    self.setupEditor('jsEditor', 'javascript');
                });
                this.$watch('$wire.activeEditorTab', function () {
                    self.$nextTick(function () {
                        self.editors.forEach(function (ed) { ed.refresh(); });
                    });
                });
            },
            setupEditor(refName, mode) {
                var textarea = this.$refs[refName];
                if (!textarea) return;
                if (textarea.nextElementSibling && textarea.nextElementSibling.classList.contains('CodeMirror')) return;

                var editor = CodeMirror.fromTextArea(textarea, {
                    mode: mode,
                    theme: 'material-darker',
                    lineNumbers: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: false,
                    lineWrapping: false,
                    matchBrackets: true,
                    autoCloseTags: mode === 'htmlmixed'
                });

                var syncTimeout = null;
                editor.on('change', function () {
                    editor.save();
                    clearTimeout(syncTimeout);
                    syncTimeout = setTimeout(function () {
                        textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    }, 300);
                });

                this.editors.push(editor);
                setTimeout(function () { editor.refresh(); }, 50);
            },
            destroyEditors() {
                this.editors.forEach(function (ed) { ed.toTextArea(); });
                this.editors = [];
            }
        };
    }

    document.addEventListener('livewire:init', function () {
        Livewire.hook('component.updated', function (component) {
            var editorRoot = component.el.querySelector('[x-data="codeMirrorEditors()"]');
            if (editorRoot && editorRoot.__x) {
                editorRoot.__x.$nextTick(function () {
                    var data = Alpine.$data(editorRoot);
                    if (data && data.initEditors) {
                        data.initEditors();
                    }
                });
            }
        });
    });
</script>
@endpush

@script
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('open-preview', (data) => {
            var win = window.open('', '_blank');
            if (win) {
                win.document.write(data[0].document);
                win.document.close();
            } else {
                Alpine.store('toasts').push('warning', 'Preview could not be opened. Please allow pop-ups for this site.');
            }
        });
    });
</script>
@endscript
