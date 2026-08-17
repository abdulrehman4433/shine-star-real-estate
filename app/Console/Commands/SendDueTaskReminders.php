<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDueReminder;
use Illuminate\Console\Command;

class SendDueTaskReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:send-due-task-reminders';

    /**
     * @var string
     */
    protected $description = 'Email agents/staff a reminder for tasks due today or overdue that have not been reminded yet';

    public function handle(): void
    {
        $tasks = Task::query()->dueForReminder()->with('assignee')->get();

        foreach ($tasks as $task) {
            $task->assignee->notify(new TaskDueReminder($task));
            $task->update(['reminded_at' => now()]);
        }

        $this->info("Sent {$tasks->count()} task reminder(s).");
    }
}
