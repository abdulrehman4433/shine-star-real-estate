<?php

namespace App\Livewire\Admin\Chat;

use App\Enums\LeadStatus;
use App\Events\NewChatMessage;
use App\Livewire\Concerns\Notifies;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;
    use Notifies;

    public string $search = '';

    public bool $unreadOnly = false;

    // 0 (not null) when nothing is selected, consistent with the sentinel pattern used by the
    // frontend ChatBox. #[Url] makes notification bell links like /admin/chat?conversation=5 work.
    #[Url(as: 'conversation')]
    public int $activeConversationId = 0;

    public string $replyBody = '';

    public function mount(): void
    {
        // Covers arriving here with ?conversation=X already in the URL (a bell link, a bookmark, a
        // refresh) — view() below only fires for in-page clicks, so opening a conversation this way
        // needs its own mark-as-read too.
        if ($this->activeConversationId) {
            $this->markRead($this->activeConversationId);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUnreadOnly(): void
    {
        $this->resetPage();
    }

    public function view(int $conversationId): void
    {
        $this->activeConversationId = $conversationId;
        $this->markRead($conversationId);
    }

    /** Opening a chat — from this list, the bell, or a direct link — marks it read. Any
     *  admin/super-admin can open any conversation via the ConversationPolicy bypass, so "read" here
     *  means "someone on staff has now seen it," not "the specific assigned recipient has."
     *  Dispatches "chat-updated" so the topbar bell (a sibling Livewire component) reflects the new
     *  count immediately instead of waiting for its own poll cycle. */
    private function markRead(int $conversationId): void
    {
        Conversation::find($conversationId)
            ?->messages()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->dispatch('chat-updated');
    }

    public function closeThread(): void
    {
        $this->activeConversationId = 0;
    }

    /** Closes the CONVERSATION itself (see Conversation::close()'s doc comment) — distinct from
     *  closeThread() above, which only deselects the currently-viewed thread panel in this admin UI. */
    public function closeConversation(int $conversationId): void
    {
        Conversation::findOrFail($conversationId)->close();
        $this->notifySuccess('Conversation closed.');
    }

    /** Wired to wire:poll in the view — this whole page had no live-refresh at all before, so a new
     *  guest/user message never showed up in the list, the unread stat cards, or an already-open
     *  thread until the admin closed and reopened it (or reloaded). Polling re-renders everything
     *  (render() always re-queries fresh), and if a conversation is currently open, treats "still
     *  open while a new message streams in" the same as "just opened it" — mark it read immediately
     *  rather than leaving it stuck unread until the admin closes and reopens it again. */
    public function poll(): void
    {
        if ($this->activeConversationId) {
            $this->markRead($this->activeConversationId);
        }
    }

    public function sendReply(): void
    {
        if (! $this->activeConversationId) {
            return;
        }

        $this->validate(['replyBody' => 'required|string|max:2000']);

        $conversation = Conversation::findOrFail($this->activeConversationId);

        if ($conversation->isClosed()) {
            $this->notifyWarning('This conversation is closed.');

            return;
        }

        // read_at is set immediately here — this message was sent BY staff, so there's no one on
        // the staff side left to "read" it; leaving it null would make the bell/unread counters
        // re-inflate the instant an admin replies to their own conversation.
        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $this->replyBody,
            'read_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new NewChatMessage($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Chat broadcast failed, message was still saved: '.$e->getMessage());
        }

        $this->reset('replyBody');
        $this->notifySuccess('Reply sent.');
    }

    /** Converts whoever is on the other end of a conversation (guest or logged-in user) into a
     *  CRM lead, so they can be followed up on later regardless of whether they ever come back
     *  to chat again. Idempotent — clicking it twice on the same conversation reuses the lead. */
    public function moveToLead(int $conversationId): void
    {
        $conversation = Conversation::with('initiator')->findOrFail($conversationId);

        if (Lead::where('conversation_id', $conversation->id)->exists()) {
            $this->notifyWarning('This conversation was already converted to a lead.');

            return;
        }

        if ($conversation->isGuestInitiated()) {
            $name = $conversation->guest_name ?: 'Guest';
            $contact = trim(collect([$conversation->guest_email, $conversation->guest_phone])->filter()->join(' / '));
        } else {
            $name = $conversation->initiator?->name ?? 'Unknown';
            $contact = trim(collect([$conversation->initiator?->email, $conversation->initiator?->phone])->filter()->join(' / '));
        }

        Lead::create([
            'property_id' => $conversation->property_id,
            'conversation_id' => $conversation->id,
            'name' => $name,
            'contact' => $contact ?: 'unknown',
            'source' => 'chat',
            'status' => LeadStatus::New->value,
            'notes' => 'Converted from a chat conversation for future follow-up.',
        ]);

        $this->notifySuccess('Converted to lead.');
    }

    public function render()
    {
        $conversations = Conversation::query()
            ->with(['initiator', 'recipient', 'property'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')])
            ->withCount('messages')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';

                $query->where(function ($q) use ($term) {
                    $q->whereHas('initiator', fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('recipient', fn ($q2) => $q2->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('property', fn ($q2) => $q2->where('title', 'like', $term))
                        ->orWhere('guest_name', 'like', $term)
                        ->orWhere('guest_email', 'like', $term);
                });
            })
            ->when($this->unreadOnly, fn ($query) => $query->whereHas('messages', fn ($q) => $q->whereNull('read_at')))
            ->orderByDesc('last_message_at')
            ->paginate(15);

        $activeConversation = $this->activeConversationId
            ? Conversation::with(['messages.sender', 'initiator', 'recipient', 'property'])->find($this->activeConversationId)
            : null;

        $totalConversations = Conversation::count();
        $unreadConversations = Conversation::whereHas('messages', fn ($q) => $q->whereNull('read_at'))->count();

        return view('livewire.admin.chat.manager', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'activeConversationLead' => $activeConversation
                ? Lead::where('conversation_id', $activeConversation->id)->first()
                : null,
            'totalConversations' => $totalConversations,
            'unreadConversations' => $unreadConversations,
            'readConversations' => $totalConversations - $unreadConversations,
        ])->extends('admin.layouts.app')->section('content');
    }
}
