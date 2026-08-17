<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'Properties']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Properties</div>
            <div class="ssm-page-header__subtitle">Manage listings, moderation, and featured status.</div>
        </div>

        <div class="d-flex gap-2">
            <select wire:model.live="statusFilter" class="form-select w-auto">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>

            <a href="{{ route('admin.properties.create') }}" class="btn btn-primary text-nowrap">
                <i class="bi bi-plus-lg"></i> Add Property
            </a>
        </div>
    </div>

    @if ($properties->isEmpty())
        <div class="card">
            <div class="card-body">
                <div class="ssm-empty-state">
                    <i class="bi bi-houses"></i>
                    <p>No properties found. Try adjusting the status filter, or add a new property.</p>
                </div>
            </div>
        </div>
    @else
        <div class="admin-card-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 mb-4">
            @foreach ($properties as $property)
                <div class="col">
                    <div class="card h-100">
                        <div class="card-img-wrap">
                            @if ($property->featured_thumb_url)
                                <img src="{{ $property->featured_thumb_url }}" alt="{{ $property->title }}">
                            @else
                                <div class="card-img-placeholder"><i class="bi bi-house"></i></div>
                            @endif

                            <div class="card-badges">
                                <span class="badge {{ $property->statusEnum()->badgeClass() ?? '' }}">
                                    {{ ucfirst($property->status) }}
                                </span>
                            </div>

                            <button type="button" class="btn btn-sm card-fav-btn {{ $property->is_featured ? 'btn-warning' : 'btn-light' }}"
                                wire:click="toggleFeatured({{ $property->id }})" title="{{ $property->is_featured ? 'Featured' : 'Not featured' }}">
                                <i class="bi {{ $property->is_featured ? 'bi-star-fill' : 'bi-star' }}"></i>
                            </button>
                        </div>

                        <div class="card-body pb-2">
                            <h6 class="card-title mb-1 text-truncate" title="{{ $property->title }}">
                                <a href="{{ route('properties.show', $property) }}" target="_blank" class="card-title-link">
                                    {{ $property->title }}
                                </a>
                            </h6>
                            <div class="small fw-semibold mb-1">{{ $property->formatted_price }}</div>
                            <div class="small text-muted text-truncate">{{ $property->category->name ?? '—' }} / {{ $property->type->name ?? '—' }}</div>
                            <div class="small text-muted text-truncate">{{ $property->owner->name }}</div>
                            @if ($property->status === 'rejected' && $property->rejection_reason)
                                <div class="small text-danger text-truncate" title="{{ $property->rejection_reason }}">{{ $property->rejection_reason }}</div>
                            @endif
                        </div>

                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>

                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" title="More actions">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if ($property->status !== 'approved')
                                        <li><button type="button" class="dropdown-item" wire:click="approve({{ $property->id }})">Approve</button></li>
                                    @endif
                                    @if ($property->status !== 'rejected')
                                        <li><button type="button" class="dropdown-item" wire:click="startReject({{ $property->id }})">Reject</button></li>
                                    @endif
                                    @if ($property->status !== 'expired')
                                        <li>
                                            <button type="button" class="dropdown-item"
                                                @click="$store.confirm.open({ message: 'Mark \'{{ $property->title }}\' as expired?', variant: 'primary' }).then(ok => ok && $wire.expireNow({{ $property->id }}))">
                                                Mark Expired
                                            </button>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $properties->links() }}
    @endif

    @if ($rejectingId)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="cancelReject">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="confirmReject">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Listing</h5>
                            <button type="button" class="btn-close" wire:click="cancelReject"></button>
                        </div>

                        <div class="modal-body">
                            <label class="form-label">Reason for rejection</label>
                            <textarea wire:model="rejectionReason" rows="3" class="form-control @error('rejectionReason') is-invalid @enderror"></textarea>
                            @error('rejectionReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="cancelReject">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
