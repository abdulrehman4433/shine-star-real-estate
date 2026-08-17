<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;

        $channels = [
            new PrivateChannel("chat.conversation.{$this->message->conversation_id}"),
            new PrivateChannel("App.Models.User.{$conversation->recipient_id}"),
        ];

        if ($conversation->initiator_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$conversation->initiator_id}");
        } else {
            // Guest-initiated: there's no authenticated initiator to subscribe to a private channel at
            // all (guests can't pass /broadcasting/auth), so this is the only live-push path a guest's
            // GuestChatBox has — see that component's echo listener. Payload stays limited to message_id
            // just like the private channels above, so the only thing a public listener can learn is "a
            // message happened on conversation N", never its content.
            $channels[] = new Channel("chat.conversation.{$this->message->conversation_id}.public");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
        ];
    }
}
