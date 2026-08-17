<?php

namespace App\Livewire\Frontend\Chat;

use App\Events\NewChatMessage;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatBox extends Component
{
    use WithFileUploads;

    // 0 (not null) when nothing is selected — Livewire's #[On] dynamic placeholder resolution
    // (`{conversationId}`) throws if the property is null, since it treats null as "unset".
    public int $conversationId = 0;

    public string $body = '';

    public $attachment = null;

    // True inside the floating widget (narrow — list and thread can't sit side by side, so the
    // view switches between them instead). False on the full /chat page (wide enough for both).
    public bool $compact = false;

    // Whether the floating widget's panel is actually visible right now — irrelevant on the full /chat
    // page (there's no minimize concept there, so it's always treated as "open"; see mount()). Gates
    // messageReceived()/refreshThread() below so a background poll/echo tick can't silently mark a
    // message read before the user has actually seen it just because the panel happens to be minimized —
    // see the chat-widget-opened/chat-widget-closed dispatches in chat-widget.blade.php.
    public bool $isOpen = false;

    public function mount(?Conversation $conversation = null, bool $compact = false): void
    {
        $this->compact = $compact;
        $this->isOpen = ! $compact;

        if ($conversation && $conversation->exists) {
            $this->authorize('view', $conversation);
            $this->conversationId = $conversation->id;
            $conversation->markReadFor(auth()->user());
        }
    }

    #[On('chat-widget-opened')]
    public function panelOpened(): void
    {
        $this->isOpen = true;

        if ($this->conversationId) {
            Conversation::find($this->conversationId)?->markReadFor(auth()->user());
        }
    }

    #[On('chat-widget-closed')]
    public function panelClosed(): void
    {
        $this->isOpen = false;
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $this->conversationId = $conversationId;
        // Explicitly picking a conversation is always a real "the user is looking at this now" action,
        // regardless of $isOpen — this method can only ever be triggered by clicking it in the (visible)
        // list, so there's no minimized-background-poll scenario to guard against here.
        $conversation->markReadFor(auth()->user());
    }

    public function backToList(): void
    {
        $this->conversationId = 0;
    }

    /** Either side can close a conversation (see Conversation::close()'s doc comment) — closing doesn't
     *  delete anything, it just means the next "Chat with Owner" click (Conversation::startBetween())
     *  starts a fresh conversation instead of resuming this one. */
    public function closeChat(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = Conversation::findOrFail($this->conversationId);
        $this->authorize('view', $conversation);

        $conversation->close();
        $this->conversationId = 0;
    }

    public function send(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = Conversation::findOrFail($this->conversationId);
        $this->authorize('view', $conversation);

        if ($conversation->isClosed()) {
            return;
        }

        $this->validate([
            'body' => 'required_without:attachment|nullable|string|max:2000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $this->body ?: null,
        ]);

        if ($this->attachment) {
            $message->addMedia($this->attachment->getRealPath())
                ->usingFileName($this->attachment->getClientOriginalName())
                ->toMediaCollection('attachment');
        }

        $conversation->update(['last_message_at' => now()]);

        // The message is already persisted at this point — a broadcast failure (e.g. the Reverb
        // server isn't running) should never prevent the message from being sent, only real-time delivery.
        try {
            broadcast(new NewChatMessage($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Chat broadcast failed, message was still saved: '.$e->getMessage());
        }

        $this->reset(['body', 'attachment']);
    }

    #[On('echo-private:chat.conversation.{conversationId},message.sent')]
    public function messageReceived(): void
    {
        // Still re-renders either way (any #[On] hit triggers a normal Livewire response, which is what
        // makes the badge/thread refresh even while minimized) — only the mark-as-read is gated on the
        // panel actually being visible.
        if ($this->conversationId && $this->isOpen) {
            Conversation::find($this->conversationId)?->markReadFor(auth()->user());
        }
    }

    // Poll-based fallback so new messages/conversations still show up on their own even when the
    // Reverb server isn't running locally — `messageReceived()` above is the real-time path, this
    // is just a safety net that re-renders periodically (see wire:poll in the view).
    public function refreshThread(): void
    {
        if ($this->conversationId && $this->isOpen) {
            Conversation::find($this->conversationId)?->markReadFor(auth()->user());
        }
    }

    public function render()
    {
        $conversations = auth()->user()->conversations()
            ->with(['initiator', 'recipient', 'property'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $activeConversation = $this->conversationId
            ? Conversation::with(['messages.sender', 'initiator', 'recipient', 'property'])->find($this->conversationId)
            : null;

        return view('livewire.frontend.chat.chat-box', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
        ]);
    }
}
