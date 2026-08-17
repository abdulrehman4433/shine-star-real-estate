<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage)
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
        $contact = $this->contactMessage;

        $mail = (new MailMessage)
            ->subject('New contact form message'.($contact->subject ? ": {$contact->subject}" : ''))
            ->line("From: {$contact->name} ({$contact->email})");

        if ($contact->phone) {
            $mail->line("Phone: {$contact->phone}");
        }

        if ($contact->page_title) {
            $mail->line("Page: {$contact->page_title}");
        }

        return $mail
            ->line('Message:')
            ->line($contact->message)
            ->action('View in Admin Panel', route('admin.contact-messages.index', ['contact' => $contact->id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['contact_message_id' => $this->contactMessage->id, 'name' => $this->contactMessage->name, 'email' => $this->contactMessage->email];
    }
}
