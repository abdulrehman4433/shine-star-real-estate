<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class GuestChatStarted extends Notification
{
    use Queueable;

    public function __construct(public Conversation $conversation, public string $excerpt)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'guest_name' => $this->conversation->guest_name,
            'property_title' => $this->conversation->property?->title,
            'excerpt' => Str::limit($this->excerpt, 80),
        ];
    }
}
