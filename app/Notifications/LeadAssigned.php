<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadAssigned extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead)
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
            ->subject("New lead assigned: {$this->lead->name}")
            ->line("A lead has been assigned to you: {$this->lead->name} ({$this->lead->contact}).")
            ->when($this->lead->property, fn ($mail) => $mail->line("Property: {$this->lead->property->title}"))
            ->action('View Lead', route('leads.show', $this->lead));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['lead_id' => $this->lead->id];
    }
}
