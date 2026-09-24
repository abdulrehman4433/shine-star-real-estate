<?php

namespace App\Livewire\Frontend\Chat;

use App\Enums\RoleName;
use App\Events\NewChatMessage;
use App\Livewire\Concerns\Notifies;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatBox extends Component
{
    use Notifies;
    use WithFileUploads;

    // 0 (not null) when nothing is selected — Livewire's #[On] dynamic placeholder resolution
    // (`{conversationId}`) throws if the property is null, since it treats null as "unset".
    public int $conversationId = 0;

    public string $body = '';

    public $attachment = null;

    // Start-form fields, shown in place of a conversation list whenever no thread is open. Same set
    // the guest form asks for, pre-filled from the account in mount() so a logged-in user normally
    // only has to type the message. name/phone are snapshotted onto the conversation on submit
    // (see startChat()); email is a read-only echo of the account address — changing it would need
    // re-verification anyway, and findOrStartGuest() resumes threads by matching guest_email, so
    // ever storing a typed address here could let a stranger land inside this user's conversation.
    public string $contactName = '';

    public string $contactPhone = '';

    public string $contactEmail = '';

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

        $user = auth()->user();
        $this->contactName = (string) ($user->name ?? '');
        $this->contactPhone = (string) ($user->phone ?? '');
        $this->contactEmail = (string) ($user->email ?? '');

        if ($conversation && $conversation->exists) {
            $this->authorize('view', $conversation);
            $this->conversationId = $conversation->id;
            $conversation->markReadFor(auth()->user());
        }
    }

    #[On('chat-widget-opened')]
    public function panelOpened(): void
    {
        // Only the nested-in-widget instance (compact) tracks the panel. The full /chat page renders
        // a *second* ChatBox on the same request as the floating widget, so without this guard both
        // instances answer the same dispatched event — closing the widget would wrongly flip the
        // full page's isOpen to false and silently disable its own poll/mark-as-read.
        if (! $this->compact) {
            return;
        }

        $this->isOpen = true;

        if ($this->conversationId) {
            Conversation::find($this->conversationId)?->markReadFor(auth()->user());
        }
    }

    #[On('chat-widget-closed')]
    public function panelClosed(): void
    {
        if (! $this->compact) {
            return;
        }

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

    public function backToForm(): void
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

    /** The start form's submit — the authenticated equivalent of GuestChatBox::startChat(), so a
     *  logged-in user gets the same form -> thread flow a guest does and can send from the frontend
     *  exactly the way a guest would. Difference: the conversation is linked to their real account
     *  (initiator_id), not recorded as a guest one, so /admin/chat shows who they actually are. */
    public function startChat(): void
    {
        $this->validate([
            'contactName' => 'required|string|max:255',
            'contactPhone' => 'required|string|max:30',
            'contactEmail' => 'nullable|email|max:255',
            'body' => 'required|string|min:2|max:2000',
        ]);

        $staff = $this->staffRecipient();

        if (! $staff) {
            $this->notifyError('No support team member is available to chat right now.');

            return;
        }

        $user = auth()->user();

        // startBetween() resumes an existing open thread between the two of them, and deliberately
        // never resumes a closed one — same rule as every other start path in the app.
        $conversation = Conversation::startBetween($user, $staff);

        // Snapshot what they actually typed so the CRM's contact card shows it; guest_email is
        // intentionally left alone for the reason documented on the property declarations.
        $conversation->update([
            'guest_name' => $this->contactName,
            'guest_phone' => $this->contactPhone,
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $this->body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Same failover as ChatBox::send() — the message is already persisted, so a missing Reverb
        // server may only cost real-time delivery, never the message itself.
        try {
            broadcast(new NewChatMessage($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Chat broadcast failed, message was still saved: '.$e->getMessage());
        }

        $this->conversationId = $conversation->id;
        $conversation->markReadFor($user);
        $this->reset(['body']);
    }

    /** Who the start form's message is addressed to. Mirrors GuestChatBox's recipient choice (first
     *  super-admin, falling back to admin) but excludes the current user, so a staff member using
     *  the form never opens a thread with themselves. */
    private function staffRecipient(): ?User
    {
        foreach ([RoleName::SuperAdmin, RoleName::Admin] as $role) {
            $candidate = User::query()
                ->whereKeyNot(auth()->id())
                ->whereHas('roles', fn ($q) => $q->where('name', $role->value))
                ->orderBy('id')
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        return null;
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

    // Role-scoped channel every admin/super-admin hears on — the conversation listener above only
    // fires for threads this viewer is actually a party to, so without it an admin's conversation
    // *list* only caught up on the 5s poll (and only while the panel was open). Deliberately does
    // NOT mark anything read: seeing that a new thread exists isn't the same as opening it.
    #[On('echo-private:staff.chat,message.sent')]
    public function staffMessageReceived(): void
    {
    }

    /** Poll target for the view's wire:poll — a safety net that keeps messages/conversations showing
     *  up on their own when the Reverb server isn't running (messageReceived() above is the
     *  real-time path). Still a no-op when nothing is selected: calling any Livewire action forces a
     *  re-render regardless of what the body does, which is exactly what refreshes the conversation
     *  *list*. The poll itself is gated on $isOpen (not $conversationId) so an open-but-empty widget
     *  keeps picking up new threads. */
    public function refreshThread(): void
    {
        if ($this->conversationId && $this->isOpen) {
            Conversation::find($this->conversationId)?->markReadFor(auth()->user());
        }
    }

    public function render()
    {
        // The floating widget never lists conversations — it is start-form + active thread only, so
        // a panel sitting on every page can't expose another person's chat, let alone a guest's.
        // /chat is the inbox, and it splits by role; see visibleConversations().
        $conversations = $this->compact
            ? collect()
            : $this->visibleConversations(auth()->user())
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

    /** Which threads the /chat inbox shows this viewer. (The floating widget shows none at all —
     *  see render().) */
    private function visibleConversations(User $user)
    {
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            // Staff: every non-guest thread. Guest chats belong to /admin/chat — and a super-admin
            // is the recipient of every general guest thread anyway, so scoping by participation
            // would never have hidden them.
            return Conversation::query()->whereNotNull('initiator_id');
        }

        // Everyone else: their own threads, minus closed guest ones (finished — the guest has been
        // bounced back to the start form, and /admin/chat keeps the history). Guest enquiries about
        // their listings deliberately stay visible: agents have no /admin/chat access, so hiding
        // them here would make those messages unreachable entirely.
        return $user->conversations()
            ->where(fn ($q) => $q->whereNull('closed_at')->orWhereNotNull('initiator_id'));
    }
}
