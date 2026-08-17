<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyApproved extends Notification
{
    use Queueable;

    public function __construct(public Property $property)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your listing \"{$this->property->title}\" has been approved")
            ->line("Good news! Your listing \"{$this->property->title}\" is now live on the site.")
            ->action('View Listing', route('properties.show', $this->property));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['property_id' => $this->property->id];
    }
}
