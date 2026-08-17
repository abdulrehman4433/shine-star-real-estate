<div>
    {{-- Header Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Page Templates</h1>
            <p class="text-muted small mb-0">Create and manage reusable page layout templates</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="showImport" title="Import">
                <i class="bi bi-download"></i> Import
            </button>
            <button type="button" class="btn btn-primary btn-sm" wire:click="createTemplate">
                <i class="bi bi-plus-lg"></i> New Template
            </button>
        </div>
    </div>

    {{-- Templates List --}}
    @if ($templates->isEmpty() && !$showEditor)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-richtext display-4 text-muted mb-3 d-block"></i>
                <h5 class="text-muted mb-2">No Page Templates Yet</h5>
                <p class="text-muted small mb-3">Create your first page template to define how pages look on the website.</p>
                <button type="button" class="btn btn-primary" wire:click="createTemplate">
                    <i class="bi bi-plus-lg"></i> Create Page Template
                </button>
            </div>
        </div>
    @elseif(!$showEditor)
        <div class="row g-3">
            @foreach ($templates as $template)
                @php $settings = $template->getSettingsWithDefaults(); @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="mb-1">{{ $template->name }}</h5>
                                    <div class="d-flex gap-2">
                                        @if ($template->is_active)
                                            <span class="badge text-bg-success">Active</span>
                                        @endif
                                        <span class="badge {{ $template->status === 'published' ? 'text-bg-primary' : 'text-bg-secondary' }}">
                                            {{ ucfirst($template->status) }}
                                        </span>
                                        <span class="badge text-bg-info">
                                            {{ $template->pages->count() }} page(s)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Mini Info --}}
                            <div class="mt-2 small text-muted">
                                <div><i class="bi bi-fonts me-1"></i> {{ $settings['content_font_family'] ?? 'Inter' }}</div>
                                <div><i class="bi bi-palette me-1"></i> {{ $settings['content_bg_color'] ?? '#fff' }}
                                    @if($settings['show_title'] ?? false) <span class="badge text-bg-light ms-1">Title</span> @endif
                                    @if($settings['enable_animation'] ?? false) <span class="badge text-bg-light ms-1">Animated</span> @endif
                                </div>
                                <div class="mt-1 text-truncate" style="max-width: 200px;">
                                    <i class="bi bi-code-slash me-1"></i> {{ Str::limit($template->css ?? '', 60) ?: 'No CSS' }}
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="mt-3 d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="editTemplate({{ $template->id }})">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                @if (!$template->is_active && $template->status === 'published')
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        @click="$store.confirm.open({ message: 'Set \'{{ $template->name }}\' as the active page template? It will be used as the default for new pages.', variant: 'primary' }).then(ok => ok && $wire.setActive({{ $template->id }}))">
                                        <i class="bi bi-check-circle"></i> Set Active
                                    </button>
                                @endif

                                @if ($template->is_active)
                                    <span class="btn btn-sm btn-success disabled">
                                        <i class="bi bi-check-circle-fill"></i> Active
                                    </span>
                                @endif

                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="duplicateTemplate({{ $template->id }})"
                                    title="Duplicate">
                                    <i class="bi bi-copy"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    wire:click="exportTemplate({{ $template->id }})" title="Export">
                                    <i class="bi bi-box-arrow-up"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete \'{{ $template->name }}\'? This cannot be undone.' }).then(ok => ok && $wire.deleteTemplate({{ $template->id }}))"
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ─── Template Editor ─── --}}
    @if ($showEditor)
        <div class="ssm-header-editor" wire:key="page-template-editor-{{ $editingId ?? 'new' }}">
            {{-- Editor Top Bar --}}
            <div class="ssm-header-editor__topbar">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeEditor">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <div>
                        <input type="text" wire:model="name"
                            class="form-control form-control-sm ssm-header-editor__name-input"
                            placeholder="Template name..." />
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" wire:click="previewTemplate"
                        wire:loading.attr="disabled">
                        <i class="bi bi-box-arrow-up-right"></i> Preview
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="saveDraft"
                        wire:loading.attr="disabled">
                        <i class="bi bi-cloud"></i> Save Draft
                    </button>
                    <button type="button" class="btn btn-sm btn-gold" wire:click="publish"
                        wire:loading.attr="disabled">
                        <i class="bi bi-upload"></i> Publish
                    </button>
                </div>
            </div>

            {{-- Main Editor Grid --}}
            <div class="ssm-header-editor__grid">
                {{-- Left Panel: Code Editors + Settings --}}
                <div class="ssm-header-editor__left">
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
                                <span class="text-muted small">Use variables: <code>@{{ $pageTitle }}</code> <code>@{!! $pageContent !!}</code></span>
                            </div>
                            <div class="ssm-cm-wrapper">
                                <textarea x-ref="htmlEditor"
                                    wire:model="html"
                                    class="form-control ssm-header-editor__textarea"
                                    rows="28"
                                    spellcheck="false"
                                    placeholder="<div class="ssm-page">...</div>"></textarea>
                            </div>
                        </div>

                        {{-- CSS Editor --}}
                        <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'css'">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 small fw-semibold">
                                    <i class="bi bi-palette"></i> CSS Styles
                                </label>
                                <span class="text-muted small">Use settings variables like <code>@{{ $contentFontFamily }}</code></span>
                            </div>
                            <div class="ssm-cm-wrapper">
                                <textarea x-ref="cssEditor"
                                    wire:model="css"
                                    class="form-control ssm-header-editor__textarea"
                                    rows="28"
                                    spellcheck="false"
                                    placeholder="/* Page template styles */"></textarea>
                            </div>
                        </div>

                        {{-- JS Editor --}}
                        <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'js'">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 small fw-semibold">
                                    <i class="bi bi-filetype-js"></i> JavaScript
                                </label>
                                <span class="text-muted small">Animations, interactions, dynamic behavior</span>
                            </div>
                            <div class="ssm-cm-wrapper">
                                <textarea x-ref="jsEditor"
                                    wire:model="js"
                                    class="form-control ssm-header-editor__textarea"
                                    rows="28"
                                    spellcheck="false"
                                    placeholder="// Custom page JS"></textarea>
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
                                        <label class="form-label small">Container Width (px)</label>
                                        <input type="number" wire:model="containerWidth" class="form-control form-control-sm" min="640" max="1920">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Title Alignment</label>
                                        <select wire:model="titleAlignment" class="form-select form-select-sm">
                                            <option value="left">Left</option>
                                            <option value="center">Center</option>
                                            <option value="right">Right</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" wire:model="showTitle" class="form-check-input" id="setting-show-title">
                                            <label class="form-check-label small" for="setting-show-title">Show Page Title</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" wire:model="enableAnimation" class="form-check-input" id="setting-animation">
                                            <label class="form-check-label small" for="setting-animation">Enable Animations</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" wire:model="shadowEnabled" class="form-check-input" id="setting-shadow">
                                            <label class="form-check-label small" for="setting-shadow">Shadow Effects</label>
                                        </div>
                                    </div>

                                    {{-- Colors --}}
                                    <div class="col-12 mt-3">
                                        <h6 class="fw-bold text-primary border-bottom pb-2">
                                            <i class="bi bi-palette me-1"></i> Colors
                                        </h6>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Content Background</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" wire:model="contentBgColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                            <input type="text" wire:model="contentBgColor" class="form-control form-control-sm" maxlength="7">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Text Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" wire:model="contentTextColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                            <input type="text" wire:model="contentTextColor" class="form-control form-control-sm" maxlength="7">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Heading Color</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" wire:model="headingColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                            <input type="text" wire:model="headingColor" class="form-control form-control-sm" maxlength="7">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Title Background</label>
                                        <div class="d-flex gap-2">
                                            <input type="color" wire:model="titleBgColor" class="form-control form-control-color p-0" style="width:36px;height:36px;">
                                            <input type="text" wire:model="titleBgColor" class="form-control form-control-sm" maxlength="7">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
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
                                        <label class="form-label small">Content Font Family</label>
                                        <input type="text" wire:model="contentFontFamily" class="form-control form-control-sm" placeholder="Inter, sans-serif">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Font Size (px)</label>
                                        <input type="number" wire:model="contentFontSize" class="form-control form-control-sm" min="10" max="32">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Line Height</label>
                                        <input type="number" wire:model="contentLineHeight" class="form-control form-control-sm" min="1" max="3" step="0.05">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Heading Font Family</label>
                                        <input type="text" wire:model="headingFontFamily" class="form-control form-control-sm" placeholder="Inter, sans-serif">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Heading Font Weight</label>
                                        <input type="number" wire:model="headingFontWeight" class="form-control form-control-sm" min="300" max="900" step="100">
                                    </div>

                                    {{-- Spacing & Shape --}}
                                    <div class="col-12 mt-3">
                                        <h6 class="fw-bold text-primary border-bottom pb-2">
                                            <i class="bi bi-arrows-expand me-1"></i> Spacing & Shape
                                        </h6>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Title Padding Y (px)</label>
                                        <input type="number" wire:model="titlePaddingY" class="form-control form-control-sm" min="0" max="200">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Border Radius (px)</label>
                                        <input type="number" wire:model="borderRadius" class="form-control form-control-sm" min="0" max="30">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Mobile Breakpoint (px)</label>
                                        <input type="number" wire:model="mobileBreakpoint" class="form-control form-control-sm" min="480" max="1200">
                                    </div>

                                    {{-- Animation --}}
                                    <div class="col-12 mt-3">
                                        <h6 class="fw-bold text-primary border-bottom pb-2">
                                            <i class="bi bi-film me-1"></i> Animation
                                        </h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Animation Style</label>
                                        <select wire:model="animationStyle" class="form-select form-select-sm">
                                            <option value="fade-up">Fade Up</option>
                                            <option value="fade">Fade In</option>
                                            <option value="slide-up">Slide Up</option>
                                            <option value="zoom">Zoom In</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    @endif

    {{-- Import Modal --}}
    @if ($showImportModal)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="showImportModal = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="importTemplate">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Page Template</h5>
                            <button type="button" class="btn-close" wire:click="showImportModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Paste Template JSON</label>
                                <textarea wire:model="importJson" rows="10" class="form-control @error('importJson') is-invalid @enderror" spellcheck="false" placeholder='{"name": "...", "html": "...", "css": "...", "js": "...", "settings": {...}}'></textarea>
                                @error('importJson') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="showImportModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
        border-color: var(--ssm-royal-blue);
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
        border-left: 2px solid var(--ssm-gold);
    }

    .ssm-cm-wrapper .CodeMirror-activeline-background {
        background: rgba(8, 63, 127, 0.08);
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

    /* Single column layout since live preview is removed */
    .ssm-header-editor__grid {
        grid-template-columns: 1fr;
    }
</style>
@endpush

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

@script
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('download-template', (data) => {
            var payload = data[0];
            var blob = new Blob([payload.json], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = payload.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });

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
