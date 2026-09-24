<?php

namespace App\Livewire\Frontend\Chat;

use App\Models\Conversation;
use App\Models\Message;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatWidget extends Component
{
    // 0 (not null sentinel) for guests — the #[On] dynamic placeholder below throws on null.
    public int $userId;

    // Tracks the count as of the last render so render() can tell "went up" (new message arrived —
    // ring the icon) apart from "went down" (guest/user just opened the panel and read it) or "same".
    // Persists across requests via Livewire's normal public-property snapshotting, so this comparison
    // still works correctly on the very next poll/echo tick, not just within one request.
    public int $lastSeenUnread = 0;

    public function mount(): void
    {
        $this->userId = auth()->id() ?? 0;
        $this->lastSeenUnread = $this->currentUnreadCount();
    }

    // Intentionally empty — the event just needs to trigger a re-render; unreadCount is recomputed in render().
    #[On('echo-private:App.Models.User.{userId},message.sent')]
    public function refreshUnreadCount(): void
    {
    }

    // Staff are often not the *recipient* of the messages that concern them — GuestChatBox addresses
    // every guest chat to one specific super-admin — so the per-user channel above only ever lights
    // up for that one person. This role-scoped channel is what actually reaches every admin and
    // super-admin on the frontend (see NewChatMessage::broadcastOn() + routes/channels.php).
    #[On('echo-private:staff.chat,message.sent')]
    public function refreshStaffUnreadCount(): void
    {
    }

    // Guests have no top-level "App.Models.User.{id}" channel to listen on directly (no user id at all),
    // so GuestChatBox — a separate, nested Livewire component — bubbles this plain event up whenever its
    // own public per-conversation echo listener fires, letting the badge/ring react instantly instead of
    // waiting for this component's own 15s poll.
    #[On('chat-message-received')]
    public function refreshOnGuestMessage(): void
    {
    }

    private function currentUnreadCount(): int
    {
        if (! auth()->check()) {
            return Conversation::unreadCountForGuestSession();
        }

        $user = auth()->user();

        // Staff's widget badge counts site-wide rather than participant-scoped — an admin is rarely
        // the *recipient* of the threads that concern them, and User::unreadMessagesCount() would
        // leave their badge stuck at zero. Guest-initiated threads are excluded outright to match
        // what the frontend actually shows: a logged-in user never sees a guest chat at the frontend
        // (ChatBox::render()), and the widget has no list for one to appear in, so counting them
        // would ring an icon with nothing behind it. Guest messages are a /admin/chat concern.
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return Message::query()
                ->whereHas('conversation', fn ($q) => $q->whereNotNull('initiator_id'))
                ->where(fn ($q) => $q->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id))
                ->whereNull('read_at')
                ->count();
        }

        return $user->unreadMessagesCount();
    }

    public function render()
    {
        $unreadCount = $this->currentUnreadCount();

        // Only ring on a genuine increase — a drop (panel just opened/marked read) or no change should
        // stay silent, otherwise the icon would "ring" on every single poll tick forever.
        if ($unreadCount > $this->lastSeenUnread) {
            $this->dispatch('chat-unread-increased');
        }

        $this->lastSeenUnread = $unreadCount;

        return view('livewire.frontend.chat.chat-widget', [
            'unreadCount' => $unreadCount,
        ]);
    }
}
