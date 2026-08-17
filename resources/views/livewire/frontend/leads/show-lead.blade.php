<div class="container py-5">
    @if (auth()->user()?->hasAnyRole(['admin', 'super-admin']))
        @include('admin.partials.breadcrumb', ['items' => [
            ['label' => 'CRM'],
            ['label' => 'Leads', 'route' => 'admin.leads.index'],
            ['label' => $lead->name],
        ]])
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="ssm-page-header__title mb-1">{{ $lead->name }}</div>
            <p class="text-muted mb-0">{{ $lead->contact }}</p>
        </div>

        <form wire:submit="updateStatus" class="d-flex gap-2">
            <select wire:model="status" class="form-select form-select-sm">
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Update</button>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-person-vcard"></i> Lead Info</h2>
                    <p class="mb-1"><strong>Source:</strong> {{ $lead->source }}</p>
                    <p class="mb-1"><strong>Assigned to:</strong> {{ $lead->assignee->name ?? 'Unassigned' }}</p>
                    @if ($lead->property)
                        <p class="mb-1">
                            <strong>Property:</strong>
                            <a href="{{ route('properties.show', $lead->property) }}">{{ $lead->property->title }}</a>
                        </p>
                    @endif
                    @if ($lead->notes)
                        <hr>
                        <p class="mb-0 text-muted small" style="white-space: pre-line;">{{ $lead->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-check2-square"></i> Tasks</h2>

                    @forelse ($lead->tasks as $task)
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="task-{{ $task->id }}"
                                {{ $task->is_completed ? 'checked' : '' }}
                                wire:click="toggleTaskComplete({{ $task->id }})">
                            <label for="task-{{ $task->id }}" class="form-check-label {{ $task->is_completed ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $task->title }}
                                <span class="d-block small {{ $task->isOverdue() ? 'text-danger' : 'text-muted' }}">
                                    Due {{ $task->due_date->format('M j, Y') }}
                                </span>
                            </label>
                        </div>
                    @empty
                        <p class="text-muted small">No tasks yet.</p>
                    @endforelse

                    <hr>

                    <form wire:submit="addTask">
                        <input type="text" wire:model="taskTitle" placeholder="Task title"
                            class="form-control form-control-sm mb-2 @error('taskTitle') is-invalid @enderror">
                        @error('taskTitle') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <textarea wire:model="taskDescription" placeholder="Description (optional)" rows="2"
                            class="form-control form-control-sm mb-2"></textarea>

                        <input type="date" wire:model="taskDueDate"
                            class="form-control form-control-sm mb-2 @error('taskDueDate') is-invalid @enderror">
                        @error('taskDueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">+ Add Task</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-clock-history"></i> Activity Timeline</h2>

                    <form wire:submit="addActivity" class="mb-4">
                        <div class="row g-2">
                            <div class="col-3">
                                <select wire:model="activityType" class="form-select form-select-sm">
                                    <option value="note">Note</option>
                                    <option value="call">Call</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="follow_up">Follow-up</option>
                                </select>
                            </div>
                            <div class="col-7">
                                <input type="text" wire:model="activityNotes" placeholder="What happened?"
                                    class="form-control form-control-sm @error('activityNotes') is-invalid @enderror">
                                @error('activityNotes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                            </div>
                        </div>
                    </form>

                    @forelse ($lead->activities as $activity)
                        <div class="d-flex mb-3">
                            <span class="badge text-bg-light text-muted me-2" style="height: fit-content;">
                                {{ ucfirst(str_replace('_', ' ', $activity->type)) }}
                            </span>
                            <div>
                                <p class="mb-0">{{ $activity->notes }}</p>
                                <p class="text-muted small mb-0">
                                    {{ $activity->user->name ?? 'System' }} &middot; {{ $activity->occurred_at?->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No activity logged yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
