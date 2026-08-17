<div class="container py-4" style="max-width: 900px;">
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Blog Posts', 'route' => 'admin.blog.posts.index'],
        ['label' => $postId ? 'Edit Post' : 'New Post'],
    ]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">{{ $postId ? 'Edit Post' : 'New Post' }}</div>
    </div>

    <form wire:submit="save">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-info-circle"></i> Post Details</h2>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select wire:model="categoryId" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>

                        <div class="input-group input-group-sm mt-2">
                            <input type="text" wire:model="newCategoryName" placeholder="New category name" class="form-control">
                            <button type="button" class="btn btn-outline-secondary" wire:click="quickAddCategory">+ Add</button>
                        </div>
                        @error('newCategoryName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tags</label>
                        <div class="dropdown">
                            <button type="button" class="form-select text-start text-truncate" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                {{ $tags->whereIn('id', $tagIds)->pluck('name')->implode(', ') ?: 'Select tags...' }}
                            </button>
                            <div class="dropdown-menu w-100 p-2" style="max-height: 220px; overflow-y: auto;">
                                @forelse ($tags as $tag)
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="tag-{{ $tag->id }}"
                                            value="{{ $tag->id }}" wire:model="tagIds">
                                        <label class="form-check-label" for="tag-{{ $tag->id }}">{{ $tag->name }}</label>
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0 px-1">No tags yet — add one below.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="input-group input-group-sm mt-2">
                            <input type="text" wire:model="newTagName" placeholder="New tag name" class="form-control">
                            <button type="button" class="btn btn-outline-secondary" wire:click="quickAddTag">+ Add</button>
                        </div>
                        @error('newTagName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Excerpt</label>
                    <input type="text" wire:model="excerpt" maxlength="255" class="form-control @error('excerpt') is-invalid @enderror">
                    @error('excerpt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Short summary shown on listing cards. Optional.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <div
                        wire:ignore
                        x-data="{
                            editor: null,
                            debounceTimer: null,
                            init() {
                                ClassicEditor.create(this.$refs.editor, {
                                    initialData: @js($content),
                                }).then((editor) => {
                                    this.editor = editor;
                                    editor.model.document.on('change:data', () => {
                                        clearTimeout(this.debounceTimer);
                                        this.debounceTimer = setTimeout(() => {
                                            $wire.set('content', editor.getData());
                                        }, 400);
                                    });
                                });
                            }
                        }"
                    >
                        <div x-ref="editor"></div>
                    </div>
                    @error('content') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select wire:model="status" class="form-select">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Publish date (optional)</label>
                        <input type="datetime-local" wire:model="publishedAt" class="form-control">
                        <div class="form-text">Leave blank to publish immediately when status is Published.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Featured image</label>
                    <input type="file" wire:model="featuredImage" accept="image/*" class="form-control @error('featuredImage') is-invalid @enderror">
                    @error('featuredImage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="featuredImage" class="form-text">Uploading...</div>
                    @if ($featuredImage)
                        <img src="{{ $featuredImage->temporaryUrl() }}" class="mt-2 rounded" width="160" height="100" style="object-fit: cover;">
                    @elseif ($existingFeaturedImage)
                        <img src="{{ $existingFeaturedImage }}" class="mt-2 rounded" width="160" height="100" style="object-fit: cover;">
                    @endif
                </div>
            </div>
        </div>

        @include('admin.partials.seo-fields')

        <div class="d-flex">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                {{ $postId ? 'Save Changes' : 'Publish/Save Post' }}
            </button>
        </div>
    </form>
</div>
