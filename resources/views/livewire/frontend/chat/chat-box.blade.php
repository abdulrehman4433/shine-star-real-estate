<div class="d-flex border rounded bg-white" style="height: 100%; min-height: 0;"
    @if ($isOpen) wire:poll.5s="refreshThread" @endif
>
    {{-- The /chat inbox list. The floating widget deliberately renders none of this — it is start
         form + active thread only, so a panel that sits on every page can never expose another
         person's conversation, let alone a guest's (see ChatBox::visibleConversations()). --}}
    @if (! $compact)
        <div class="overflow-auto border-end" style="width: 260px; flex-shrink: 0;">
            <div class="d-flex align-items-center border-bottom px-3 py-2 sticky-top bg-white">
                <span class="small fw-semibold text-muted">Conversations</span>
            </div>

            @forelse ($conversations as $conversation)
                @php
                    $label = $conversation->participantLabel(auth()->user());
                    $unread = $conversation->unreadCountFor(auth()->user());
                @endphp
                <button type="button"
                    wire:click="selectConversation({{ $conversation->id }})"
                    class="btn btn-link text-decoration-none text-start w-100 border-bottom rounded-0 px-3 py-2 {{ $conversation->id === $conversationId ? 'bg-light' : '' }}"
                >
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold text-dark">
                                {{ $label }}
                                @if ($conversation->isGuestInitiated())
                                    <span class="badge text-bg-info-subtle text-info-emphasis">Guest</span>
                                @endif
                                @if ($conversation->isClosed())
                                    <span class="badge text-bg-secondary">Closed</span>
                                @endif
                            </div>
                            @if ($conversation->property)
                                <div class="text-muted small">{{ $conversation->property->title }}</div>
                            @endif
                        </div>
                        @if ($unread > 0)
                            <span class="badge text-bg-danger rounded-pill">{{ $unread }}</span>
                        @endif
                    </div>
                </button>
            @empty
                <p class="text-muted small p-3 mb-0">No conversations yet.</p>
            @endforelse
        </div>
    @endif

    <div class="d-flex flex-column flex-grow-1" style="min-width: 0;">
        @if ($activeConversation)
            @php $label = $activeConversation->participantLabel(auth()->user()); @endphp

            <div class="border-bottom px-3 py-2 d-flex align-items-center gap-2">
                @if ($compact)
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" wire:click="backToForm" title="Back">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                @endif
                <div class="text-truncate flex-grow-1">
                    <strong>{{ $label }}</strong>
                    @if ($activeConversation->property)
                        <span class="text-muted small"> &middot; {{ $activeConversation->property->title }}</span>
                    @endif
                    @if ($activeConversation->isClosed())
                        <span class="badge text-bg-secondary ms-1">Closed</span>
                    @endif
                </div>
                @unless ($activeConversation->isClosed())
                    <button type="button" wire:click="closeChat" wire:confirm="Close this chat?" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="Close chat">
                        <i class="bi bi-x-circle"></i>
                    </button>
                @endunless
            </div>

            <div class="flex-grow-1 overflow-auto px-3 py-2" style="min-height: 0;" x-data
                x-init="$el.scrollTop = $el.scrollHeight"
                wire:key="thread-{{ $activeConversation->id }}-{{ $activeConversation->messages->count() }}">
                @foreach ($activeConversation->messages as $message)
                    @php
                        // A staff reply sent from the admin CRM panel can come from neither
                        // participant — label it so it isn't mistaken for the other participant.
                        $isThirdParty = $message->sender_id
                            && $message->sender_id !== $activeConversation->initiator_id
                            && $message->sender_id !== $activeConversation->recipient_id;
                    @endphp
                    <div class="mb-2 d-flex {{ $message->sender_id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="ssm-msg-bubble {{ $message->sender_id === auth()->id() ? 'is-mine' : 'is-theirs' }}">
                            @if ($isThirdParty)
                                <div class="small fw-semibold">{{ $message->sender->name }} (Support)</div>
                            @endif
                            @if ($message->body)
                                <div style="white-space: pre-line;">{{ $message->body }}</div>
                            @endif
                            @if ($message->attachment_url)
                                <a href="{{ $message->attachment_url }}" target="_blank" class="{{ $message->sender_id === auth()->id() ? 'text-white' : '' }}">
                                    📎 Attachment
                                </a>
                            @endif
                            <div class="small {{ $message->sender_id === auth()->id() ? 'text-white-50' : 'text-muted' }}">
                                {{ $message->created_at->format('g:i A') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($activeConversation->isClosed())
                <div class="border-top p-3 text-center text-muted small">
                    This conversation has been closed. Start a new chat with this contact to continue.
                </div>
            @else
                <form wire:submit="send" class="ssm-reply-form border-top p-2">
                    <label class="ssm-contact-btn mb-0" title="Attach a file" style="cursor: pointer;">
                        <i class="bi bi-paperclip"></i>
                        <input type="file" wire:model="attachment" class="d-none">
                    </label>
                    <div class="ssm-reply-input">
                        <input type="text" wire:model="body" placeholder="Type a message..." class="ssm-reply-input__field">
                    </div>
                    @if ($attachment)
                        <span class="small text-muted text-truncate" style="max-width: 70px;" title="{{ $attachment->getClientOriginalName() }}">
                            {{ $attachment->getClientOriginalName() }}
                        </span>
                    @endif
                    <button type="submit" class="btn btn-primary ssm-reply-send">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
                @error('body') <div class="text-danger small px-2">{{ $message }}</div> @enderror
            @endif
        @else
            {{-- Start form: what a logged-in user sees instead of a conversation list. Same four
                 fields the guest form asks for, pre-filled from the account (see ChatBox::mount()),
                 so the flow — and the message that lands in /admin/chat — matches a guest's. --}}
            <form wire:submit="startChat" class="d-flex flex-column gap-2 p-3 overflow-auto" style="flex-grow: 1; min-height: 0;">
                <div>
                    <label class="form-label fw-semibold" style="font-size: .78rem; color: #495057; margin-bottom: .2rem;">Name <span class="text-danger">*</span></label>
                    <input type="text" wire:model="contactName" placeholder="Your full name" class="form-control form-control-sm @error('contactName') is-invalid @enderror" required>
                    @error('contactName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label fw-semibold" style="font-size: .78rem; color: #495057; margin-bottom: .2rem;">Phone <span class="text-danger">*</span></label>
                    <input type="tel" wire:model="contactPhone" placeholder="Your phone number" class="form-control form-control-sm @error('contactPhone') is-invalid @enderror" required>
                    @error('contactPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="form-label fw-semibold" style="font-size: .78rem; color: #495057; margin-bottom: .2rem;">Email <span class="text-muted" style="font-weight: 400;">(your account email)</span></label>
                    <input type="email" wire:model="contactEmail" readonly class="form-control form-control-sm text-muted">
                </div>

                <div>
                    <label class="form-label fw-semibold" style="font-size: .78rem; color: #495057; margin-bottom: .2rem;">Message <span class="text-danger">*</span></label>
                    <textarea wire:model="body" rows="3" placeholder="How can we help you?" class="form-control form-control-sm @error('body') is-invalid @enderror" required></textarea>
                    @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary ssm-reply-send justify-content-center">
                    <i class="bi bi-send-fill"></i>
                    <span>Start Chat</span>
                </button>
            </form>
        @endif
    </div>
</div>
