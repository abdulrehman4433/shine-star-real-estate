<?php

namespace App\Livewire\Frontend\Leads;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Livewire\Component;
use Livewire\WithPagination;

class MyLeads extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $leads = Lead::query()
            ->assignedTo(auth()->id())
            ->with('property')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.frontend.leads.my-leads', [
            'leads' => $leads,
            'statuses' => LeadStatus::cases(),
        ])->extends('frontend.layouts.app')->section('content');
    }
}
