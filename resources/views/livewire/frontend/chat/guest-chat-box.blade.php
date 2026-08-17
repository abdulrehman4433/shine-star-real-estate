<div class="d-flex flex-column border rounded bg-white" style="height: 100%; min-height: 0;"
    @if ($step === 'thread') wire:poll.5s="refreshThread" @endif
>
    @if ($step === 'form')
        <form wire:submit="startChat" class="p-3 d-flex flex-column gap-2 overflow-auto">
            <p class="text-muted small mb-1">
                Tell us a bit about yourself and we'll get right back to you.
            </p>

            <div>
                <input type="text" wire:model="name" placeholder="Your name" class="form-control form-control-sm @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <input type="email" wire:model="email" placeholder="Email address" class="form-control form-control-sm @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <input type="text" wire:model="phone" placeholder="Phone (optional)" class="form-control form-control-sm @error('phone') is-invalid @enderror">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div>
                <textarea wire:model="message" rows="3" placeholder="How can we help?" class="form-control form-control-sm @error('message') is-invalid @enderror"></textarea>
                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary ssm-reply-send justify-content-center">
                <i class="bi bi-send-fill"></i>
                <span>Start Chat</span>
            </button>
        </form>
    @else
        @if ($activeConversation)
            <div class="border-bottom px-3 py-2 d-flex align-items-center justify-content-between">
                <div>
                    <strong>{{ $activeConversation->guest_name }}</strong>
                    @if ($activeConversation->property)
                        <span class="text-muted small"> &middot; {{ $activeConversation->property->title }}</span>
                    @endif
                    @if ($activeConversation->isClosed())
                        <span class="badge text-bg-secondary ms-1">Closed</span>
                    @endif
                </div>
                @unless ($activeConversation->isClosed())
                    <button type="button" wire:click="closeChat" wire:confirm="Close this chat?" class="btn btn-sm btn-outline-secondary" title="Close chat">
                        <i class="bi bi-x-circle"></i>
                    </button>
                @endunless
            </div>

            <div class="flex-grow-1 overflow-auto px-3 py-2" style="min-height: 0;" x-data
                x-init="$el.scrollTop = $el.scrollHeight"
                wire:key="guest-thread-{{ $activeConversation->id }}-{{ $activeConversation->messages->count() }}">
                @foreach ($activeConversation->messages as $msg)
                    <div class="mb-2 d-flex {{ is_null($msg->sender_id) ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="ssm-msg-bubble {{ is_null($msg->sender_id) ? 'is-mine' : 'is-theirs' }}">
                            @if (! is_null($msg->sender_id))
                                <div class="small fw-semibold">{{ $msg->sender->name }}</div>
                            @endif
                            @if ($msg->body)
                                <div style="white-space: pre-line;">{{ $msg->body }}</div>
                            @endif
                            <div class="small {{ is_null($msg->sender_id) ? 'text-white-50' : 'text-muted' }}">
                                {{ $msg->created_at->format('g:i A') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($activeConversation->isClosed())
                <div class="border-top p-3 text-center">
                    <p class="text-muted small mb-2">This conversation has been closed.</p>
                    <button type="button" wire:click="startNewChat" class="btn btn-sm btn-primary">
                        Start New Chat
                    </button>
                </div>
            @else
                <form wire:submit="send" class="ssm-reply-form border-top p-2">
                    <div class="ssm-reply-input">
                        <input type="text" wire:model="body" placeholder="Type a message..." class="ssm-reply-input__field">
                    </div>
                    <button type="submit" class="btn btn-primary ssm-reply-send">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
                @error('body') <div class="text-danger small px-2">{{ $message }}</div> @enderror
            @endif
        @else
            <div class="d-flex align-items-center justify-content-center flex-grow-1 text-muted small p-3">
                This conversation is no longer available.
            </div>
        @endif
    @endif
</div>
