<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CMS'], ['label' => 'Reviews']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Customer Reviews</div>
            <div class="ssm-page-header__subtitle">
                These reviews power the "Good Reviews by Customers" section on the homepage.
                Only reviews marked <strong>Visible</strong> are shown, in the order listed below (drag to reorder).
                You can turn the whole section on/off from <a href="{{ route('admin.settings.index') }}">Admin &raquo; Settings</a>.
            </div>
        </div>
        <button type="button" class="btn btn-primary text-nowrap" wire:click="createReview">
            <i class="bi bi-plus-lg"></i> Add Review
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($reviews->isEmpty())
                <div class="ssm-empty-state">
                    <i class="bi bi-chat-quote"></i>
                    <p>No reviews yet. Add your first customer review to show it on the homepage.</p>
                </div>
            @else
                <ul class="list-group list-group-flush" x-sort="(id, position) => $wire.reorder(id, position)">
                    @foreach ($reviews as $review)
                        <li class="list-group-item d-flex align-items-start gap-3"
                            x-sort:item="{{ $review->id }}" wire:key="review-{{ $review->id }}">
                            <span x-sort:handle class="mt-1"><i class="bi bi-grip-vertical"></i></span>

                            @if ($review->photo_thumb_url)
                                <img src="{{ $review->photo_thumb_url }}" alt="{{ $review->customer_name }}"
                                    class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted"
                                    style="width: 48px; height: 48px; flex-shrink: 0;">
                                    <i class="bi bi-person"></i>
                                </div>
                            @endif

                            <div class="flex-grow-1 {{ $review->is_active ? '' : 'text-muted' }}">
                                <div class="fw-semibold">
                                    {{ $review->customer_name }}
                                    @if ($review->customer_role)
                                        <span class="text-muted small fw-normal">&mdash; {{ $review->customer_role }}</span>
                                    @endif
                                    @unless ($review->is_active)
                                        <span class="badge text-bg-secondary ms-1">Hidden</span>
                                    @endunless
                                </div>
                                <div class="small text-warning mb-1">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                    @endfor
                                </div>
                                <div class="small">{{ Str::limit($review->content, 150) }}</div>
                            </div>

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" wire:click="toggleActive({{ $review->id }})">
                                    <i class="bi {{ $review->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i> {{ $review->is_active ? 'Hide' : 'Show' }}
                                </button>
                                <button type="button" class="btn btn-outline-primary" wire:click="editReview({{ $review->id }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button type="button" class="btn btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete this review?' }).then(ok => ok && $wire.delete({{ $review->id }}))">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if ($showModal)
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:keydown.escape="closeModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $editingId ? 'Edit Review' : 'Add Review' }}</h5>
                            <button type="button" class="btn-close" wire:click="closeModal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" wire:model="customer_name" class="form-control @error('customer_name') is-invalid @enderror">
                                @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role / Location (optional)</label>
                                <input type="text" wire:model="customer_role" placeholder="e.g. Karachi, Property Buyer" class="form-control @error('customer_role') is-invalid @enderror">
                                @error('customer_role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <select wire:model="rating" class="form-select @error('rating') is-invalid @enderror">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </select>
                                @error('rating') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Review Text</label>
                                <textarea wire:model="content" rows="4" class="form-control @error('content') is-invalid @enderror"></textarea>
                                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Photo (optional)</label>
                                @if ($existingPhotoUrl)
                                    <div class="mb-2">
                                        <img src="{{ $existingPhotoUrl }}" alt="" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                                    </div>
                                @endif
                                <input type="file" wire:model="photo" class="form-control @error('photo') is-invalid @enderror">
                                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div wire:loading wire:target="photo" class="small text-muted mt-1">Uploading...</div>
                            </div>

                            <div class="form-check form-switch">
                                <input type="checkbox" wire:model="is_active" class="form-check-input" id="review-active">
                                <label class="form-check-label" for="review-active">Visible</label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
