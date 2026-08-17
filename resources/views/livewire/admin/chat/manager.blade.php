<div x-data="{ contact: null }" wire:poll.5s="poll">
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CRM'], ['label' => 'Chat']]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Chat (CRM)</div>
        <div class="ssm-page-header__subtitle">Monitor and reply to conversations across the site.</div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon"><i class="bi bi-chat-dots"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Total Conversations</div>
                    <div class="ssm-stat-card__value">{{ $totalConversations }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-danger"><i class="bi bi-envelope-exclamation"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Unread Conversations</div>
                    <div class="ssm-stat-card__value">{{ $unreadConversations }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-success"><i class="bi bi-check2-circle"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Read Conversations</div>
                    <div class="ssm-stat-card__value">{{ $readConversations }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" wire:model.live.debounce.400ms="search"
                            placeholder="Search by name, email, or property..." class="form-control form-control-sm">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" wire:model.live="unreadOnly" class="form-check-input" id="unread-only">
                        <label class="form-check-label small" for="unread-only">Unread only</label>
                    </div>

                    <div class="list-group" style="max-height: 520px; overflow-y: auto;">
                        @forelse ($conversations as $conversation)
                            @php $contact = $conversation->initiatorContact(); @endphp
                            <div class="list-group-item ssm-chat-list-item d-flex align-items-start gap-2 p-0 {{ $activeConversation?->id === $conversation->id ? 'active' : '' }}">
                                <button type="button" class="ssm-contact-btn m-2" title="View contact details"
                                    @click.stop="contact = { name: @js($contact['name']), email: @js($contact['email']), phone: @js($contact['phone']) }">
                                    <i class="bi bi-person-circle"></i>
                                </button>
                                <button type="button" wire:click="view({{ $conversation->id }})"
                                    class="btn text-start flex-grow-1 py-2 pe-3 ps-0 border-0 rounded-0 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="text-truncate flex-grow-1 me-2" style="min-width: 0;">
                                            <div class="fw-semibold text-truncate">
                                                {{ $conversation->initiatorLabel() }} &harr; {{ $conversation->recipient?->name }}
                                                @if ($conversation->isClosed())
                                                    <span class="badge text-bg-secondary">Closed</span>
                                                @endif
                                            </div>
                                            @if ($conversation->property)
                                                <div class="small {{ $activeConversation?->id === $conversation->id ? '' : 'text-muted' }}">
                                                    {{ $conversation->property->title }}
                                                </div>
                                            @endif
                                            <div class="small {{ $activeConversation?->id === $conversation->id ? '' : 'text-muted' }}">
                                                {{ $conversation->messages_count }} messages &middot;
                                                {{ $conversation->last_message_at?->diffForHumans() ?? 'no messages yet' }}
                                            </div>
                                        </div>
                                        @if ($conversation->unread_count > 0)
                                            <span class="badge text-bg-danger rounded-pill">{{ $conversation->unread_count }}</span>
                                        @endif
                                    </div>
                                </button>
                            </div>
                        @empty
                            <div class="ssm-empty-state py-4">
                                <i class="bi bi-chat-square-text"></i>
                                <p class="small">No conversations match your filters.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-3">
                        {{ $conversations->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card" style="min-height: 520px;">
                @if ($activeConversation)
                    @php $activeContact = $activeConversation->initiatorContact(); @endphp
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="ssm-contact-btn" title="View contact details"
                                @click="contact = { name: @js($activeContact['name']), email: @js($activeContact['email']), phone: @js($activeContact['phone']) }">
                                <i class="bi bi-person-circle"></i>
                            </button>
                            <div>
                                <strong>{{ $activeConversation->initiatorLabel() }}</strong> &harr; <strong>{{ $activeConversation->recipient?->name }}</strong>
                                @if ($activeConversation->isClosed())
                                    <span class="badge text-bg-secondary">Closed</span>
                                @endif
                                @if ($activeConversation->property)
                                    <div class="text-muted small">{{ $activeConversation->property->title }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($activeConversationLead)
                                <a href="{{ route('leads.show', $activeConversationLead) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-person-lines-fill"></i> View Lead
                                </a>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-success" wire:click="moveToLead({{ $activeConversation->id }})">
                                    <i class="bi bi-arrow-right-circle"></i> Move to Leads
                                </button>
                            @endif
                            @unless ($activeConversation->isClosed())
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="closeConversation({{ $activeConversation->id }})" wire:confirm="Close this conversation?">
                                    <i class="bi bi-x-circle"></i> Close Chat
                                </button>
                            @endunless
                            <button type="button" class="btn-close" wire:click="closeThread" aria-label="Close"></button>
                        </div>
                    </div>

                    <div class="card-body overflow-auto" style="max-height: 440px;" x-data
                        x-init="$el.scrollTop = $el.scrollHeight"
                        wire:key="admin-thread-{{ $activeConversation->id }}-{{ $activeConversation->messages->count() }}">
                        @forelse ($activeConversation->messages as $message)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold small">
                                        {{ $message->sender?->name ?? ($activeConversation->guest_name ?: 'Guest') }}
                                    </span>
                                    <span class="text-muted small">{{ $message->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                @if ($message->body)
                                    <div style="white-space: pre-line;">{{ $message->body }}</div>
                                @endif
                                @if ($message->attachment_url)
                                    <a href="{{ $message->attachment_url }}" target="_blank">📎 Attachment</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">No messages in this conversation yet.</p>
                        @endforelse
                    </div>

                    @if ($activeConversation->isClosed())
                        <div class="card-footer text-center text-muted small">
                            This conversation is closed. It'll reopen automatically as a new conversation the
                            next time this contact starts a chat.
                        </div>
                    @else
                        <form wire:submit="sendReply" class="ssm-reply-form card-footer">
                            <div class="ssm-reply-input @error('replyBody') has-error @enderror">
                                <i class="bi bi-chat-left-text ssm-reply-input__icon"></i>
                                <input type="text" wire:model="replyBody" placeholder="Type a reply..."
                                    class="ssm-reply-input__field">
                            </div>
                            <button type="submit" class="btn btn-primary ssm-reply-send">
                                <i class="bi bi-send-fill"></i>
                                <span>Send</span>
                            </button>
                        </form>
                        @error('replyBody') <div class="text-danger small px-3 pb-2">{{ $message }}</div> @enderror
                    @endif
                @else
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">
                        Select a conversation to view the full thread.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" style="background: rgba(5, 45, 100, .45);"
        :class="{ 'd-block': contact }" @click.self="contact = null" @keydown.escape.window="contact = null">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 1rem;">
                <div class="modal-header">
                    <h6 class="modal-title mb-0">Contact Details</h6>
                    <button type="button" class="btn-close" @click="contact = null" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-person-fill text-muted"></i>
                        <span x-text="contact?.name || '&mdash;'"></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-envelope-fill text-muted"></i>
                        <span x-text="contact?.email || 'Not provided'"></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-telephone-fill text-muted"></i>
                        <span x-text="contact?.phone || 'Not provided'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
