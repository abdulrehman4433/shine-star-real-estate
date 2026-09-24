<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'initiator_id',
        'recipient_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'last_message_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function otherParticipant(User $user): User
    {
        return $user->id === $this->initiator_id ? $this->recipient : $this->initiator;
    }

    public function isGuestInitiated(): bool
    {
        return is_null($this->initiator_id);
    }

    public function isClosed(): bool
    {
        return ! is_null($this->closed_at);
    }

    /** Either side can close a conversation — signals "this is resolved". Doesn't delete anything;
     *  a closed conversation stays fully visible in history for both the admin and the other party.
     *  The only behavioral effect is that startBetween()/findOrStartGuest() will never resume a closed
     *  conversation — the next message from either side starts a genuinely new one instead. */
    public function close(): void
    {
        $this->update(['closed_at' => now()]);
    }

    public function scopeOpenOnly($query)
    {
        return $query->whereNull('closed_at');
    }

    /** Safe display name for "who is on the other end" — handles guest-initiated conversations,
     *  where there is no initiator User row to fall back on. */
    public function participantLabel(User $viewer): string
    {
        if ($this->isGuestInitiated()) {
            return $this->guest_name ?: 'Guest';
        }

        return $this->otherParticipant($viewer)->name;
    }

    /** Display label for the initiator side, independent of any particular viewer — used by
     *  admin-facing views (CRM Chat manager, notification bell) that show both sides at once. */
    public function initiatorLabel(): string
    {
        return $this->isGuestInitiated()
            ? ($this->guest_name ?: 'Guest').' (Guest)'
            : (string) $this->initiator?->name;
    }

    /** Contact card for whoever initiated the conversation (guest or logged-in user) — used by the
     *  admin CRM Chat panel's "view contact details" icon/modal. */
    public function initiatorContact(): array
    {
        if ($this->isGuestInitiated()) {
            return [
                'name' => $this->guest_name ?: 'Guest',
                'email' => $this->guest_email,
                'phone' => $this->guest_phone,
            ];
        }

        // guest_* doubles as a snapshot of whatever a logged-in user typed into the widget's start
        // form (ChatBox::startChat()), so the CRM shows what they actually submitted rather than
        // whatever happens to be on their account row. guest_email is never written for these
        // threads, so email always falls back to the account — see the ChatBox property docblock.
        return [
            'name' => $this->guest_name ?: $this->initiator?->name,
            'email' => $this->guest_email ?: $this->initiator?->email,
            'phone' => $this->guest_phone ?: $this->initiator?->phone,
        ];
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where(fn ($q) => $q->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id))
            ->whereNull('read_at')
            ->count();
    }

    public function markReadFor(User $user): void
    {
        $this->messages()
            ->where(fn ($q) => $q->whereNull('sender_id')->orWhere('sender_id', '!=', $user->id))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Guest equivalent of unreadCountFor()/markReadFor() — a guest's own messages always have
     *  sender_id = null, so "unread for the guest" is simply any staff-sent message not yet read. */
    public function unreadCountForGuest(): int
    {
        return $this->messages()->whereNotNull('sender_id')->whereNull('read_at')->count();
    }

    public function markReadForGuest(): void
    {
        $this->messages()->whereNotNull('sender_id')->whereNull('read_at')->update(['read_at' => now()]);
    }

    /** Every conversation id currently tracked in the guest's session — mirrors GuestChatBox::sessionKey()'s
     *  key shape (one 'guest_conversation_general' plus one 'guest_conversation_property_{id}' per property
     *  context the guest has chatted about), so a guest can have more than one active conversation at once. */
    public static function guestSessionConversationIds(): array
    {
        return collect(session()->all())
            ->filter(fn ($value, $key) => is_string($key) && str_starts_with($key, 'guest_conversation_'))
            ->values()
            ->all();
    }

    /** Total unread (staff-sent) messages across every conversation the current guest session is tracking —
     *  the guest equivalent of User::unreadMessagesCount(), used by the floating chat widget's badge. */
    public static function unreadCountForGuestSession(): int
    {
        $ids = static::guestSessionConversationIds();

        if (empty($ids)) {
            return 0;
        }

        return Message::query()
            ->whereIn('conversation_id', $ids)
            ->whereNotNull('sender_id')
            ->whereNull('read_at')
            ->count();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(fn ($q) => $q->where('initiator_id', $userId)->orWhere('recipient_id', $userId));
    }

    /** Find the existing OPEN conversation between two users (optionally about a property), or start a
     *  new one. A closed conversation is never resumed here — see close()'s doc comment — so once either
     *  side closes a thread, the next "Chat with Owner" click starts a fresh one instead of reopening it. */
    public static function startBetween(User $a, User $b, ?Property $property = null): self
    {
        $propertyId = $property?->id;

        $existing = self::query()
            ->where('property_id', $propertyId)
            ->openOnly()
            ->where(function ($q) use ($a, $b) {
                $q->where(fn ($q2) => $q2->where('initiator_id', $a->id)->where('recipient_id', $b->id))
                    ->orWhere(fn ($q2) => $q2->where('initiator_id', $b->id)->where('recipient_id', $a->id));
            })
            ->first();

        return $existing ?? self::create([
            'property_id' => $propertyId,
            'initiator_id' => $a->id,
            'recipient_id' => $b->id,
        ]);
    }

    /** Start a brand-new conversation on behalf of an unauthenticated visitor. Prefer findOrStartGuest()
     *  from the actual "start chat" flow — this is the unconditional "always create" primitive it falls
     *  back to, kept separate so both intents stay easy to tell apart at the call site. */
    public static function startGuest(array $guest, User $recipient, ?Property $property = null): self
    {
        return self::create([
            'property_id' => $property?->id,
            'initiator_id' => null,
            'recipient_id' => $recipient->id,
            'guest_name' => $guest['name'],
            'guest_email' => $guest['email'],
            'guest_phone' => $guest['phone'] ?? null,
        ]);
    }

    /** The guest equivalent of startBetween(): resumes the guest's existing OPEN conversation with this
     *  recipient/property if one exists (matched by email — deliberately independent of the browser
     *  session, so the same person emailing in from a new tab/device/incognito window still lands back
     *  in the same thread rather than fragmenting their history), refreshing name/phone in case they
     *  changed. If none exists, or the only prior one is closed, starts a genuinely new conversation —
     *  same "closed means don't resume" rule as startBetween().
     *
     *  When the guest provides no email (null), email-based resume is skipped entirely — there's no
     *  stable identifier to match across sessions, so a new conversation is always created. */
    public static function findOrStartGuest(array $guest, User $recipient, ?Property $property = null): self
    {
        $query = self::query()
            ->where('recipient_id', $recipient->id)
            ->where('property_id', $property?->id)
            // A guest must never be resumed into a thread a logged-in user started. ChatBox::startChat()
            // now creates open conversations with the very same recipient/property as a guest widget
            // chat, and since the email filter below is skipped whenever the guest omits their address,
            // this query would otherwise match one and hand an anonymous visitor that user's history.
            ->whereNull('initiator_id')
            ->openOnly();

        // Only attempt email-based resume when the guest actually provided an email — without one
        // there's no stable cross-session identifier to match on.
        if (! empty($guest['email'])) {
            $query->where('guest_email', $guest['email']);
        }

        $existing = $query->latest('created_at')->first();

        if ($existing) {
            $existing->update([
                'guest_name' => $guest['name'],
                'guest_phone' => $guest['phone'] ?? $existing->guest_phone,
                'guest_email' => $guest['email'] ?? $existing->guest_email,
            ]);

            return $existing;
        }

        return self::startGuest($guest, $recipient, $property);
    }
}
