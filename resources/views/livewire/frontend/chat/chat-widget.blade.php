<div
    x-data="{ open: false }"
    wire:poll.15s="$refresh"
    style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; display: flex; flex-direction: column; align-items: flex-end;"
>
    <div class="ssm-chat-widget mb-2" style="width: 340px; height: 480px;" x-show="open" x-cloak x-transition>
        <div class="ssm-chat-widget__header">
            <strong><i class="bi bi-chat-dots-fill me-2"></i>Messages</strong>
            <button type="button" class="btn-close btn-close-white" @click="open = false; $wire.dispatch('chat-widget-closed')" aria-label="Close"></button>
        </div>
        <div style="height: calc(100% - 48px);">
            @auth
                @livewire('frontend.chat.chat-box', ['compact' => true], key('chat-widget-box'))
            @else
                @livewire('frontend.chat.guest-chat-box', [], key('chat-widget-guest-box'))
            @endauth
        </div>
    </div>

    <button type="button"
        x-data="{ ring: false }"
        x-on:chat-unread-increased.window="ring = true; setTimeout(() => ring = false, 1000)"
        :class="{ 'ssm-chat-widget__toggle--ring': ring }"
        @click="open = !open; $wire.dispatch(open ? 'chat-widget-opened' : 'chat-widget-closed')"
        class="ssm-chat-widget__toggle position-relative" title="Chat">
        <i class="bi" :class="open ? 'bi-x-lg' : 'bi-chat-dots-fill'"></i>
        @if ($unreadCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $unreadCount }}
            </span>
        @endif
    </button>
</div>
