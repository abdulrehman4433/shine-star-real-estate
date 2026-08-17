<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'Blog Posts']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Blog Posts</div>
            <div class="ssm-page-header__subtitle">Write and publish articles, manage categories and tags inline.</div>
        </div>
        <div class="d-flex gap-2">
            <select wire:model.live="statusFilter" class="form-select w-auto">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
            <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> New Post</a>
        </div>
    </div>

    @if ($posts->isEmpty())
        <div class="card">
            <div class="card-body">
                <div class="ssm-empty-state">
                    <i class="bi bi-file-text"></i>
                    <p>No blog posts yet. Write your first post to get started.</p>
                </div>
            </div>
        </div>
    @else
        <div class="admin-card-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 mb-4">
            @foreach ($posts as $post)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-img-wrap">
                            @if ($post->featured_thumb_url)
                                <img src="{{ $post->featured_thumb_url }}" alt="{{ $post->title }}">
                            @else
                                <div class="card-img-placeholder"><i class="bi bi-file-text"></i></div>
                            @endif

                            <div class="card-badges">
                                <span class="badge {{ $post->statusEnum()->badgeClass() }}">
                                    {{ $post->statusEnum()->label() }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body pb-2">
                            <h6 class="card-title mb-1 text-truncate" title="{{ $post->title }}">
                                @if ($post->isPublished())
                                    <a href="{{ route('blog.show', $post) }}" target="_blank" class="card-title-link">{{ $post->title }}</a>
                                @else
                                    {{ $post->title }}
                                @endif
                            </h6>
                            <div class="small text-muted text-truncate">{{ $post->category->name ?? '—' }}</div>
                            <div class="small text-muted text-truncate">{{ $post->author->name }}</div>
                            <div class="small text-muted">{{ $post->published_at?->format('M j, Y') ?? '—' }}</div>
                        </div>

                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="toggleStatus({{ $post->id }})" title="{{ $post->isPublished() ? 'Unpublish' : 'Publish' }}">
                                <i class="bi {{ $post->isPublished() ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                            </button>

                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.blog.posts.edit', $post) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete \'{{ $post->title }}\'?' }).then(ok => ok && $wire.delete({{ $post->id }}))"
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $posts->links() }}
    @endif
</div>
