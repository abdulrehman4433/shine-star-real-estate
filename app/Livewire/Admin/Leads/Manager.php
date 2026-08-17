<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Livewire\Concerns\Notifies;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssigned;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;
    use Notifies;

    public string $statusFilter = '';

    public function mount(): void
    {
        Gate::authorize('assign', Lead::class);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function assign(int $leadId, string $agentId): void
    {
        Gate::authorize('assign', Lead::class);

        $lead = Lead::findOrFail($leadId);
        $lead->update(['assigned_to' => $agentId ?: null]);

        if ($agentId) {
            $lead->assignee->notify(new LeadAssigned($lead));
            $this->notifySuccess("Lead assigned to {$lead->assignee->name}.");
        } else {
            $this->notifySuccess('Lead unassigned.');
        }
    }

    public function render()
    {
        $leads = Lead::query()
            ->with(['property', 'assignee'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        $agents = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [RoleName::Agent->value, RoleName::Agency->value]))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.leads.manager', [
            'leads' => $leads,
            'agents' => $agents,
            'statuses' => LeadStatus::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
