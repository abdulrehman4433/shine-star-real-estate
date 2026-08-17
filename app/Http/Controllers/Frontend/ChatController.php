<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Property;

class ChatController extends Controller
{
    public function index(?Conversation $conversation = null)
    {
        if ($conversation) {
            $this->authorize('view', $conversation);
        }

        return view('frontend.chat.index', compact('conversation'));
    }

    public function start(Property $property)
    {
        abort_if($property->user_id === auth()->id(), 403, "You can't start a chat about your own listing.");

        $conversation = Conversation::startBetween(auth()->user(), $property->owner, $property);

        return redirect()->route('chat.index', $conversation);
    }
}
