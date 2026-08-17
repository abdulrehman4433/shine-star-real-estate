<?php

namespace App\Notifications;

use App\Models\PropertyInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPropertyInquiry extends Notification
{
    use Queueable;

    public function __construct(public PropertyInquiry $inquiry)
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
        $property = $this->inquiry->property;

        return (new MailMessage)
            ->subject("New inquiry: {$property->title}")
            ->line("A new inquiry was submitted for \"{$property->title}\".")
            ->line("From: {$this->inquiry->name} ({$this->inquiry->email})")
            ->when($this->inquiry->phone, fn ($mail) => $mail->line("Phone: {$this->inquiry->phone}"))
            ->line('Message:')
            ->line($this->inquiry->message)
            ->action('View Listing', route('properties.show', $property));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'inquiry_id' => $this->inquiry->id,
            'property_id' => $this->inquiry->property_id,
        ];
    }
}
