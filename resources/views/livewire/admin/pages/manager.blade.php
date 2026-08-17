<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'Pages']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Pages</div>
            <div class="ssm-page-header__subtitle">Build custom pages with drag-and-drop content blocks.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleTrashView">
                @if ($showTrash)
                    <i class="bi bi-arrow-left"></i> Back to Pages
                @else
                    <i class="bi bi-trash3"></i> Trash
                    @if ($trashedCount > 0)
                        <span class="badge text-bg-secondary ms-1">{{ $trashedCount }}</span>
                    @endif
                @endif
            </button>
            @unless ($showTrash)
                <button type="button" class="btn btn-primary" wire:click="createPage">
                    <i class="bi bi-plus-lg"></i> Add Page
                </button>
            @endunless
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($showTrash)
                <p class="text-muted small mb-3">Trashed pages are hidden from the live site and the main list, but not
                    gone yet — restore one to bring it back, or permanently delete it to remove it for good.</p>
            @endif

            @if ($pages->isEmpty())
                <div class="ssm-empty-state">
                    <i class="bi {{ $showTrash ? 'bi-trash3' : 'bi-file-earmark-text' }}"></i>
                    <p>{{ $showTrash ? 'Trash is empty.' : 'No pages yet. Click "Add Page" to create the first one.' }}</p>
                </div>
            @elseif ($showTrash)
                <ul class="list-group list-group-flush">
                    @foreach ($pages as $page)
                        <li class="list-group-item d-flex align-items-center gap-2" wire:key="trashed-page-{{ $page->id }}">
                            <span class="flex-grow-1">
                                {{ $page->title }}
                                @if($page->slug)
                                    <span class="text-muted small">/{{ $page->slug }}</span>
                                @else
                                    <span class="badge text-bg-warning ms-1">Home</span>
                                @endif
                                <span class="text-muted small ms-2">Deleted {{ $page->deleted_at->diffForHumans() }}</span>
                            </span>

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" wire:click="restore({{ $page->id }})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Permanently delete \'{{ $page->title }}\'? This cannot be undone.' }).then(ok => ok && $wire.forceDelete({{ $page->id }}))">
                                    <i class="bi bi-trash3-fill"></i> Delete Permanently
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position)">
                    @foreach ($pages as $page)
                        <li class="list-group-item d-flex align-items-center gap-2"
                            x-sort:item="{{ $page->id }}" wire:key="page-{{ $page->id }}">
                            <span x-sort:handle><i class="bi bi-grip-vertical"></i></span>

                            <span class="flex-grow-1">
                                {{ $page->title }}
                                @if($page->html)
                                    <span class="badge text-bg-info ms-1" title="Has custom HTML/CSS/JS">Custom</span>
                                @endif
                                @if($page->slug)
                                    <span class="text-muted small">/{{ $page->slug }}</span>
                                @else
                                    <span class="badge text-bg-warning ms-1" title="This page serves as the home page">Home</span>
                                @endif
                            </span>

                            <span class="badge {{ $page->statusEnum()->badgeClass() }}">
                                {{ $page->statusEnum()->label() }}
                            </span>

                            <div class="btn-group btn-group-sm">
                                @if ($page->isPublished())
                                    <a href="{{ $page->slug ? route('pages.show', $page) : route('home') }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-eye"></i> View</a>
                                @endif
                                <button type="button" class="btn btn-outline-secondary" wire:click="toggleStatus({{ $page->id }})">
                                    <i class="bi {{ $page->isPublished() ? 'bi-eye-slash' : 'bi-cloud-upload' }}"></i> {{ $page->isPublished() ? 'Unpublish' : 'Publish' }}
                                </button>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Move \'{{ $page->title }}\' to trash? You can restore it later from the Trash view.', confirmText: 'Move to Trash' }).then(ok => ok && $wire.delete({{ $page->id }}))">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
