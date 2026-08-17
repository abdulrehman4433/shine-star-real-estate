<?php

namespace App\Livewire\Frontend\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Livewire\Component;

class ShowLead extends Component
{
    public Lead $lead;

    public string $status = '';

    public string $activityType = 'note';

    public string $activityNotes = '';

    public string $taskTitle = '';

    public string $taskDescription = '';

    public string $taskDueDate = '';

    public function mount(Lead $lead): void
    {
        $this->authorize('view', $lead);

        $this->lead = $lead;
        $this->status = $lead->status;
        $this->taskDueDate = now()->addDay()->toDateString();
    }

    public function updateStatus(): void
    {
        $this->authorize('update', $this->lead);

        $this->validate(['status' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, LeadStatus::cases()))]);

        $this->lead->update(['status' => $this->status]);
    }

    public function addActivity(): void
    {
        $this->authorize('update', $this->lead);

        $this->validate([
            'activityType' => 'required|in:call,meeting,note,follow_up',
            'activityNotes' => 'required|string|max:2000',
        ]);

        $this->lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => $this->activityType,
            'notes' => $this->activityNotes,
            'occurred_at' => now(),
        ]);

        $this->reset(['activityNotes']);
        $this->lead->load('activities');
    }

    public function addTask(): void
    {
        $this->authorize('update', $this->lead);

        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string|max:2000',
            'taskDueDate' => 'required|date',
        ]);

        $this->lead->tasks()->create([
            'assigned_to' => $this->lead->assigned_to ?? auth()->id(),
            'title' => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'due_date' => $this->taskDueDate,
        ]);

        $this->reset(['taskTitle', 'taskDescription']);
        $this->taskDueDate = now()->addDay()->toDateString();
        $this->lead->load('tasks');
    }

    public function toggleTaskComplete(int $taskId): void
    {
        $this->authorize('update', $this->lead);

        $task = $this->lead->tasks()->findOrFail($taskId);
        $task->update([
            'is_completed' => ! $task->is_completed,
            'completed_at' => $task->is_completed ? null : now(),
        ]);

        $this->lead->load('tasks');
    }

    public function render()
    {
        $this->lead->loadMissing(['property', 'assignee', 'inquiry', 'activities.user', 'tasks']);

        $layout = auth()->user()?->hasAnyRole(['admin', 'super-admin'])
            ? 'admin.layouts.app'
            : 'frontend.layouts.app';

        return view('livewire.frontend.leads.show-lead', [
            'statuses' => LeadStatus::cases(),
        ])->extends($layout)->section('content');
    }
}
