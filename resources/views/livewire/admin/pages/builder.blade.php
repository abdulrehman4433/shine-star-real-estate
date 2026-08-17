<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'CMS'],
        ['label' => 'Pages', 'route' => 'admin.pages.index'],
        ['label' => $title ?: 'Edit Page'],
    ]])

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Edit Page</div>
        </div>
    </div>

    {{-- Page Settings Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-gear"></i> Page Settings</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" wire:click="previewTemplate"
                        wire:loading.attr="disabled">
                        <i class="bi bi-box-arrow-up-right"></i> Preview
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="saveDraft"
                        wire:loading.attr="disabled">
                        <i class="bi bi-cloud"></i> Save Draft
                    </button>
                    <button type="button" class="btn btn-sm btn-primary"
                        wire:loading.attr="disabled"
                        @click="$store.confirm.open({ message: '{{ !$slug && !$page->isPublished() ? 'This page has no slug and will be set as the home page. Only one published home page is allowed. Are you sure?' : 'Publish this page?' }}', variant: 'primary' }).then(ok => ok && $wire.publish())">
                        <i class="bi bi-upload"></i> Save &amp; Publish
                    </button>
                </div>
            </div>

            <form wire:submit="saveMeta">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Slug (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ url('/') }}/</span>
                            <input type="text" wire:model="slug" class="form-control @error('slug') is-invalid @enderror" placeholder="leave empty for home page">
                        </div>
                        @error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <div class="form-text">Leave blank to set this page as the home page. Only one published home page is allowed.</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Meta title</label>
                        <input type="text" wire:model="metaTitle" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Meta description</label>
                        <input type="text" wire:model="metaDescription" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Meta keywords</label>
                        <input type="text" wire:model="seoMetaKeywords" placeholder="comma, separated, keywords" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" wire:model="seoCanonicalUrl" placeholder="Defaults to this page's own URL" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
            </form>
        </div>
    </div>

    {{-- Code Editor Section --}}
    <div class="ssm-header-editor" wire:key="page-editor-{{ $page->id }}">
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
        </div>

        {{-- Editor Content --}}
        <div class="ssm-header-editor__content"
             x-data="codeMirrorEditors()"
             x-init="initEditors()"
             wire:key="code-editors-{{ $page->id }}">
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
                        placeholder='<div class="ssm-page">...</div>'></textarea>
                </div>
            </div>

            {{-- CSS Editor --}}
            <div class="ssm-header-editor__code-area" x-show="$wire.activeEditorTab === 'css'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0 small fw-semibold">
                        <i class="bi bi-palette"></i> CSS Styles
                    </label>
                </div>
                <div class="ssm-cm-wrapper">
                    <textarea x-ref="cssEditor"
                        wire:model="css"
                        class="form-control ssm-header-editor__textarea"
                        rows="28"
                        spellcheck="false"
                        placeholder="/* Page styles */"></textarea>
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
