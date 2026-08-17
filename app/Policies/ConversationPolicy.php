<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * The two participants can view/use a conversation; admins/super-admins can monitor any
     * conversation from the CRM Chat panel.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->initiator_id
            || $user->id === $conversation->recipient_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }
}
