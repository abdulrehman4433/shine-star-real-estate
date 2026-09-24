<div
    x-data="{ open: false }"
    wire:poll.15s="$refresh"
    style="position: fixed; bottom: 40px; right: 20px; z-index: 1050; display: flex; flex-direction: column; align-items: flex-end;"
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

    {{-- Sits between the popup and the chat toggle so it always renders directly above the chat
         icon — with the popup closed (the usual case) it's simply the top of the two buttons.
         Plain anchor, no Livewire round-trip. digits-only number, per the wa.me format.
         Shrinks to half size while the chat popup is open (shares the `open` state from the
         root x-data) so the popup has room; restoring on close is pure CSS. --}}
    <a href="https://wa.me/923355117928"
        target="_blank" rel="noopener"
        class="ssm-whatsapp-toggle"
        :class="{ 'ssm-whatsapp-toggle--collapsed': open }"
        style="margin-bottom: 16px;"
        title="Chat on WhatsApp"
        aria-label="Chat on WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
    </a>

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
