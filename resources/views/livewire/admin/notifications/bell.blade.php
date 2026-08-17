<div class="notif-bell" x-data="{ open: false }" wire:poll.10s
    @keydown.escape.window="if (open) { open = false; $refs.trigger.focus(); }">
    <button
        type="button"
        x-ref="trigger"
        class="notif-bell__trigger"
        @click="open = !open"
        aria-haspopup="true"
        :aria-expanded="open"
        aria-label="Notifications"
    >
        <i class="bi bi-bell"></i>
        @if ($unreadCount > 0)
            <span class="notif-bell__badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div
        class="notif-bell__panel"
        role="menu"
        aria-label="Notifications"
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="notif-bell__panel-enter"
        x-transition:enter-start="notif-bell__panel-enter-start"
        x-transition:enter-end="notif-bell__panel-enter-end"
        x-transition:leave="notif-bell__panel-leave"
        x-transition:leave-start="notif-bell__panel-leave-start"
        x-transition:leave-end="notif-bell__panel-leave-end"
    >
        <div class="notif-bell__header">
            <span class="notif-bell__title">Notifications</span>
            <button type="button" class="notif-bell__mark-all" wire:click="markAllRead"
                @if ($unreadCount === 0) disabled @endif>
                Mark All Read
            </button>
        </div>

        <div class="notif-bell__list">
            @forelse ($conversations as $conversation)
                <button type="button" role="menuitem" wire:click="goToConversation({{ $conversation->id }})"
                    class="notif-bell__item {{ $conversation->unread_count > 0 ? 'is-unread' : '' }}">
                    <span class="notif-bell__icon">
                        <i class="bi {{ $conversation->isGuestInitiated() ? 'bi-person-circle' : 'bi-chat-dots' }}"></i>
                    </span>
                    <span class="notif-bell__content">
                        <span class="notif-bell__item-title">
                            {{ $conversation->initiatorLabel() }} &harr; {{ $conversation->recipient?->name }}
                        </span>
                        <span class="notif-bell__item-preview">
                            @if ($conversation->property){{ $conversation->property->title }} &middot; @endif
                            {{ $conversation->latestMessage?->body }}
                        </span>
                        <span class="notif-bell__item-time">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                    </span>
                    @if ($conversation->unread_count > 0)
                        <span class="notif-bell__dot" aria-hidden="true"></span>
                    @endif
                </button>
            @empty
                <div class="notif-bell__empty">
                    <i class="bi bi-check2-circle" style="font-size: 1.5rem;"></i>
                    <div class="mt-2">You're all caught up.</div>
                </div>
            @endforelse
        </div>

        <div class="notif-bell__footer">
            <a href="{{ route('admin.chat.index') }}" class="notif-bell__view-all">View All Notifications &rarr;</a>
        </div>
    </div>
</div>
