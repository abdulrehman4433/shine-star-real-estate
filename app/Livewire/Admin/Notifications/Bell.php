<?php

namespace App\Livewire\Admin\Notifications;

use App\Livewire\Concerns\Notifies;
use App\Models\Conversation;
use App\Models\Message;
use Livewire\Attributes\On;
use Livewire\Component;

class Bell extends Component
{
    use Notifies;

    // Fires when any other component on the page (currently Admin\Chat\Manager, on opening or
    // replying to a conversation) marks messages read, so the badge count updates immediately
    // instead of waiting for the wire:poll below. Livewire dispatches reach every component on the
    // page by default, so no explicit wiring is needed beyond this attribute.
    #[On('chat-updated')]
    public function refresh(): void
    {
        //
    }

    /** Bulk-marks every currently-unread conversation as read — not just the ones shown in the
     *  (limited) dropdown list. */
    public function markAllRead(): void
    {
        Message::query()->whereNull('read_at')->update(['read_at' => now()]);

        $this->notifySuccess('All notifications marked as read.');
    }

    // Deliberately not named "open" — a wrapping <div x-data="{ open: false }"> in the view defines
    // its own `open` in the same Alpine scope, which would shadow a same-named Livewire method and
    // silently resolve to the Alpine boolean instead (see Module 6 docs for the bug this caused).
    public function goToConversation(int $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->messages()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->redirect(route('admin.chat.index', ['conversation' => $conversationId]));
    }

    private function unreadConversations()
    {
        return Conversation::query()
            ->with(['initiator', 'recipient', 'property', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')])
            ->whereHas('messages', fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('last_message_at')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        $conversations = $this->unreadConversations();

        return view('livewire.admin.notifications.bell', [
            'conversations' => $conversations,
            'unreadCount' => $conversations->count(),
        ]);
    }
}
