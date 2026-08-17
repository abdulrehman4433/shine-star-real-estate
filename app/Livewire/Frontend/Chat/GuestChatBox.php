<?php

namespace App\Livewire\Frontend\Chat;

use App\Enums\RoleName;
use App\Models\Conversation;
use App\Models\Property;
use App\Models\User;
use App\Notifications\GuestChatStarted;
use Livewire\Attributes\On;
use Livewire\Component;

class GuestChatBox extends Component
{
    public ?int $propertyId = null;

    public string $step = 'form';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public int $conversationId = 0;

    public string $body = '';

    public function mount(?int $propertyId = null): void
    {
        $this->propertyId = $propertyId;

        $existingId = session($this->sessionKey());

        if ($existingId && Conversation::whereKey($existingId)->exists()) {
            $this->conversationId = $existingId;
            $this->step = 'thread';
        }
    }

    private function sessionKey(): string
    {
        return $this->propertyId
            ? "guest_conversation_property_{$this->propertyId}"
            : 'guest_conversation_general';
    }

    public function startChat(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|min:2|max:2000',
        ]);

        $property = $this->propertyId ? Property::find($this->propertyId) : null;
        $recipient = $property?->owner ?? User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::SuperAdmin->value))
            ->first();

        if (! $recipient) {
            $this->addError('message', 'Chat is currently unavailable — please use the contact form instead.');

            return;
        }

        $conversation = Conversation::findOrStartGuest([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ], $recipient, $property);

        $conversation->messages()->create([
            'sender_id' => null,
            'body' => $this->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        session([$this->sessionKey() => $conversation->id]);

        $this->notifyStaff($conversation, $recipient, $this->message);

        $this->conversationId = $conversation->id;
        $this->step = 'thread';
        $this->reset(['message']);
    }

    public function send(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $this->validate([
            'body' => 'required|string|max:2000',
        ]);

        $conversation = Conversation::findOrFail($this->conversationId);

        if ($conversation->isClosed()) {
            return;
        }

        $conversation->messages()->create([
            'sender_id' => null,
            'body' => $this->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->notifyStaff($conversation, $conversation->recipient, $this->body);

        $this->reset(['body']);
    }

    /** Either side can close a conversation (see Conversation::close()'s doc comment) — for the guest,
     *  closing also forgets the session key and returns to the start-chat form, so the widget is ready
     *  for a genuinely new conversation next time rather than silently resuming the now-closed one. */
    public function closeChat(): void
    {
        if ($this->conversationId) {
            Conversation::find($this->conversationId)?->close();
        }

        session()->forget($this->sessionKey());
        $this->reset(['conversationId', 'step', 'name', 'email', 'phone', 'message', 'body']);
    }

    /** Explicit "start a new chat" action from the closed-conversation state (see the view) — same
     *  session-forgetting reset as closeChat(), without re-closing an already-closed conversation. */
    public function startNewChat(): void
    {
        session()->forget($this->sessionKey());
        $this->reset(['conversationId', 'step', 'name', 'email', 'phone', 'message', 'body']);
    }

    /** Notify the conversation's recipient plus every admin/super-admin so the CRM chat bell always lights up. */
    private function notifyStaff(Conversation $conversation, User $recipient, string $excerpt): void
    {
        $staff = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [RoleName::Admin->value, RoleName::SuperAdmin->value]))
            ->get()
            ->push($recipient)
            ->unique('id');

        foreach ($staff as $user) {
            $user->notify(new GuestChatStarted($conversation, $excerpt));
        }
    }

    // Poll-based fallback (see wire:poll in the view) — kept alongside the live echo listener below in
    // case the Reverb server isn't running, same failover pattern the authenticated ChatBox already uses.
    public function refreshThread(): void
    {
        //
    }

    // Real-time push for a staff reply. Guests can't authenticate to a private channel at all (no
    // /broadcasting/auth session), so NewChatMessage additionally broadcasts guest-initiated conversations
    // on a PUBLIC per-conversation channel purely for this — see that event's broadcastOn(). Re-renders
    // this component (picking up the new message immediately, whether the panel is open or minimized) and
    // bubbles a plain browser event so the floating widget's badge/ring (owned by the separate ChatWidget
    // component) updates instantly too, instead of waiting for its own 15s poll.
    #[On('echo:chat.conversation.{conversationId}.public,message.sent')]
    public function messageReceivedLive(): void
    {
        $this->dispatch('chat-message-received');
    }

    // Fired by the floating widget's toggle button (see chat-widget.blade.php) the moment the guest actually
    // opens the panel — deliberately NOT done inside refreshThread()/mount(), since both run continuously in
    // the background regardless of whether the panel is visually open (this component is always mounted, just
    // hidden via Alpine x-show in the parent widget), which would otherwise mark messages read before the
    // guest ever saw them.
    #[On('chat-widget-opened')]
    public function markAsRead(): void
    {
        if ($this->conversationId) {
            Conversation::find($this->conversationId)?->markReadForGuest();
        }
    }

    public function render()
    {
        $activeConversation = $this->conversationId
            ? Conversation::with(['messages.sender', 'property'])->find($this->conversationId)
            : null;

        return view('livewire.frontend.chat.guest-chat-box', [
            'activeConversation' => $activeConversation,
        ]);
    }
}
