<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminder extends Notification
{
    use Queueable;

    public function __construct(public Task $task)
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
        $due = $this->task->due_date->isPast() ? 'was due' : 'is due';

        return (new MailMessage)
            ->subject("Reminder: {$this->task->title}")
            ->line("Your task \"{$this->task->title}\" {$due} on {$this->task->due_date->format('M j, Y')}.")
            ->when($this->task->description, fn ($mail) => $mail->line($this->task->description))
            ->when($this->task->lead, fn ($mail) => $mail->action('View Lead', route('leads.show', $this->task->lead)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['task_id' => $this->task->id];
    }
}
