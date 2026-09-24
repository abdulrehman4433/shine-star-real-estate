<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Role-scoped channel every admin/super-admin subscribes to from the frontend chat widget — the
// per-user channel above only reaches the one conversation recipient, which is not the same set of
// people (guest chats, for instance, are all addressed to a single super-admin).
Broadcast::channel('staff.chat', function ($user) {
    return $user->hasAnyRole(['admin', 'super-admin']);
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    return $conversation && $user->can('view', $conversation);
});
