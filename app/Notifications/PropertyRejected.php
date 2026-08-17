<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyRejected extends Notification
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
            ->subject("Your listing \"{$this->property->title}\" was not approved")
            ->line("Your listing \"{$this->property->title}\" was not approved for the following reason:")
            ->line($this->property->rejection_reason ?? 'No reason was provided.')
            ->line('You can edit the listing and resubmit it for review.')
            ->action('Edit Listing', route('agent.listings.edit', $this->property));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['property_id' => $this->property->id];
    }
}
