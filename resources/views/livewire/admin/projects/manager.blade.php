<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'Projects']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Projects</div>
            <div class="ssm-page-header__subtitle">Manage real-estate developments, blocks, and payment plans.</div>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-grow-1 flex-wrap gap-2">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search title or society..." class="form-control" style="width: 240px;">
            <div class="d-flex gap-2">
                <div class="input-group" style="width: auto;">
                    <span class="input-group-text bg-white"><i class="bi bi-funnel"></i></span>
                    <select wire:model.live="typeFilter" class="form-select" style="min-width: 170px;">
                        <option value="">All types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('admin.projects.create') }}" class="btn btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> New Project</a>
            </div>
        </div>
    </div>

    @if ($projects->isEmpty())
        <div class="card">
            <div class="card-body">
                <div class="ssm-empty-state">
                    <i class="bi bi-buildings"></i>
                    <p>No projects yet. Try adjusting the type filter, or add a new project.</p>
                </div>
            </div>
        </div>
    @else
        <div class="admin-card-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 mb-4">
            @foreach ($projects as $project)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-img-wrap">
                            @if ($project->cover_thumb_url)
                                <img src="{{ $project->cover_thumb_url }}" alt="{{ $project->title }}">
                            @else
                                <div class="card-img-placeholder"><i class="bi bi-buildings"></i></div>
                            @endif

                            <div class="card-badges">
                                <span class="badge {{ $project->typeEnum()->badgeClass() }}">{{ $project->typeEnum()->label() }}</span>
                                @unless ($project->is_active)
                                    <span class="badge text-bg-secondary">Hidden</span>
                                @endunless
                            </div>

                            <button type="button" class="btn btn-sm card-fav-btn {{ $project->is_featured ? 'btn-warning' : 'btn-light' }}"
                                wire:click="toggleFeatured({{ $project->id }})" title="{{ $project->is_featured ? 'Featured' : 'Not featured' }}">
                                <i class="bi {{ $project->is_featured ? 'bi-star-fill' : 'bi-star' }}"></i>
                            </button>
                        </div>

                        <div class="card-body pb-2">
                            <h6 class="card-title mb-1 text-truncate" title="{{ $project->title }}">
                                <a href="{{ route('admin.projects.show', $project) }}" class="card-title-link">
                                    {{ $project->title }}
                                </a>
                            </h6>
                            <div class="small text-muted text-truncate">{{ $project->society_name }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-grid-3x3-gap"></i> {{ $project->blocks_count }} blocks
                                &middot;
                                <i class="bi bi-rulers"></i> {{ $project->plot_sizes_count }} options
                            </div>
                        </div>

                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm {{ $project->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}" wire:click="toggleActive({{ $project->id }})" title="{{ $project->is_active ? 'Visible' : 'Hidden' }}">
                                <i class="bi {{ $project->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                            </button>

                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-secondary" title="View">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete \'{{ $project->title }}\'? This will also remove its blocks and size options.' }).then(ok => ok && $wire.delete({{ $project->id }}))"
                                    title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $projects->links() }}
    @endif
</div>
