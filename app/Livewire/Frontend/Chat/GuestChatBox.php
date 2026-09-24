<?php

namespace App\Livewire\Frontend\Chat;

use App\Enums\RoleName;
use App\Events\NewChatMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use App\Notifications\GuestChatStarted;
use Illuminate\Support\Facades\Log;
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

        if ($existingId) {
            $conversation = Conversation::whereKey($existingId)->first();

            // If the conversation was closed (by admin or guest), clear the session and
            // show the fresh form — the guest always sees the input form, never a closed thread.
            if (! $conversation || $conversation->isClosed()) {
                session()->forget($this->sessionKey());

                return;
            }

            $this->conversationId = $conversation->id;
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
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
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
            'email' => $this->email ?: null,
            'phone' => $this->phone,
        ], $recipient, $property);

        $message = $conversation->messages()->create([
            'sender_id' => null,
            'body' => $this->message,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->broadcastMessage($message);

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

        // If the conversation was closed (e.g. by admin while the guest was typing),
        // clear the session and show the fresh form instead of silently doing nothing.
        if ($conversation->isClosed()) {
            session()->forget($this->sessionKey());
            $this->reset(['conversationId', 'step', 'body']);

            return;
        }

        $message = $conversation->messages()->create([
            'sender_id' => null,
            'body' => $this->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->broadcastMessage($message);

        $this->notifyStaff($conversation, $conversation->recipient, $this->body);

        $this->reset(['body']);
    }

    /** The guest -> staff direction was the only send path in the app that never broadcast: both
     *  ChatBox::send() (logged-in) and Admin\Chat\Manager::sendReply() already fired NewChatMessage,
     *  so a guest's message reached nobody in real time — not the addressed recipient, not the other
     *  admins on the frontend widget — and only ever surfaced via polling. Same channels as
     *  everywhere else: the per-conversation private channel, the recipient's user channel, the
     *  role-scoped staff.chat channel, plus the public per-conversation channel this component's own
     *  messageReceivedLive() listener rides on. */
    private function broadcastMessage(Message $message): void
    {
        try {
            broadcast(new NewChatMessage($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Chat broadcast failed, message was still saved: '.$e->getMessage());
        }
    }

    /** Either side can close a conversation (see Conversation::close()'s doc comment) — for the guest,
     *  closing forgets the session key and returns to the start-chat form immediately. */
    public function closeChat(): void
    {
        if ($this->conversationId) {
            Conversation::find($this->conversationId)?->close();
        }

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

        // mount() only checks for a closed thread on the initial page load, so if an admin closes
        // the conversation while the guest has the panel open, the guest would otherwise keep seeing
        // the finished thread (and its composer) until a full reload. Drop back to the start form —
        // exactly the state mount() lands in when it finds the session pointing at a closed thread.
        if ($activeConversation && $activeConversation->isClosed()) {
            session()->forget($this->sessionKey());
            $this->reset(['conversationId', 'step', 'body']);
            $activeConversation = null;
        }

        return view('livewire.frontend.chat.guest-chat-box', [
            'activeConversation' => $activeConversation,
        ]);
    }
}
