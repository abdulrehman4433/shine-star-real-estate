<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CRM'], ['label' => 'Contact Messages']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Contact Messages (CRM)</div>
        <div class="ssm-page-header__subtitle">Every submission from the site's contact form.</div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon"><i class="bi bi-envelope"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Total Messages</div>
                    <div class="ssm-stat-card__value">{{ $totalMessages }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-danger"><i class="bi bi-envelope-exclamation"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Unread</div>
                    <div class="ssm-stat-card__value">{{ $unreadMessages }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-success"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Read</div>
                    <div class="ssm-stat-card__value">{{ $readMessages }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form wire:submit="saveNotifySettings" class="row g-2 align-items-end">
                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input type="checkbox" wire:model="notifyEnabled" class="form-check-input" id="notify-enabled" role="switch">
                        <label class="form-check-label small" for="notify-enabled">Receive emails for new messages</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="text" wire:model="notifyEmail" placeholder="Send to (defaults to Contact Email in Settings)" class="form-control form-control-sm @error('notifyEmail') is-invalid @enderror">
                    @error('notifyEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" wire:model.live.debounce.400ms="search"
                            placeholder="Search by name, email, or subject..." class="form-control form-control-sm">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" wire:model.live="unreadOnly" class="form-check-input" id="unread-only">
                        <label class="form-check-label small" for="unread-only">Unread only</label>
                    </div>

                    <div class="list-group" style="max-height: 520px; overflow-y: auto;">
                        @forelse ($messages as $item)
                            <button type="button" wire:click="view({{ $item->id }})"
                                class="list-group-item list-group-item-action ssm-chat-list-item {{ $activeMessage?->id === $item->id ? 'active' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="text-truncate flex-grow-1 me-2" style="min-width: 0;">
                                        <div class="fw-semibold text-truncate {{ $item->isRead() ? '' : 'text-dark' }}">
                                            {{ $item->name }}
                                        </div>
                                        <div class="small {{ $activeMessage?->id === $item->id ? '' : 'text-muted' }}">
                                            {{ $item->subject ?: 'No subject' }}
                                        </div>
                                        <div class="small {{ $activeMessage?->id === $item->id ? '' : 'text-muted' }}">
                                            {{ $item->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    @unless ($item->isRead())
                                        <span class="badge text-bg-danger rounded-pill">New</span>
                                    @endunless
                                </div>
                            </button>
                        @empty
                            <div class="ssm-empty-state py-4">
                                <i class="bi bi-envelope"></i>
                                <p class="small">No contact messages match your filters.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-3">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card" style="min-height: 520px;">
                @if ($activeMessage)
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $activeMessage->name }}</strong>
                            <div class="text-muted small">{{ $activeMessage->email }}{{ $activeMessage->phone ? ' · '.$activeMessage->phone : '' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($activeMessage->isRead())
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="markUnread({{ $activeMessage->id }})">
                                    Mark Unread
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $activeMessage->id }})" wire:confirm="Delete this message?">
                                <i class="bi bi-trash"></i>
                            </button>
                            <button type="button" class="btn-close" wire:click="closeThread" aria-label="Close"></button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($activeMessage->subject)
                            <h2 class="h6">{{ $activeMessage->subject }}</h2>
                        @endif
                        @if ($activeMessage->page_title)
                            <p class="text-muted small mb-2">Submitted from: {{ $activeMessage->page_title }}</p>
                        @endif
                        <p style="white-space: pre-line;">{{ $activeMessage->message }}</p>
                        <p class="text-muted small mb-0">{{ $activeMessage->created_at->format('M j, Y g:i A') }}</p>
                    </div>

                    <form wire:submit="sendReply" class="card-footer">
                        <label class="form-label small fw-semibold">Reply via email</label>
                        <textarea wire:model="replyBody" rows="3" placeholder="Type your reply..." class="form-control form-control-sm mb-2 @error('replyBody') is-invalid @enderror"></textarea>
                        @error('replyBody') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="sendReply">
                            <span wire:loading.remove wire:target="sendReply"><i class="bi bi-send-fill"></i> Send Reply</span>
                            <span wire:loading wire:target="sendReply">Sending&hellip;</span>
                        </button>
                    </form>
                @else
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">
                        Select a message to view it.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
