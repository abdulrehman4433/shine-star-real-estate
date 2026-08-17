<?php

namespace App\Livewire\Admin\Properties;

use App\Enums\PropertyStatus;
use App\Jobs\GenerateSitemap;
use App\Models\Property;
use App\Notifications\PropertyApproved;
use App\Notifications\PropertyRejected;
use App\Livewire\Concerns\Notifies;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use Notifies, WithPagination;

    public string $statusFilter = '';

    public ?int $rejectingId = null;

    public string $rejectionReason = '';

    public function mount(): void
    {
        Gate::authorize('moderate', Property::class);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        Gate::authorize('moderate', Property::class);

        $property = Property::findOrFail($id);
        $property->update([
            'status' => PropertyStatus::Approved->value,
            'rejection_reason' => null,
        ]);

        $property->owner->notify(new PropertyApproved($property));

        GenerateSitemap::dispatch();

        $this->notifySuccess('Property approved.');
    }

    public function startReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->rejectionReason = '';
    }

    public function confirmReject(): void
    {
        Gate::authorize('moderate', Property::class);

        $this->validate([
            'rejectionReason' => 'required|string|max:500',
        ]);

        $property = Property::findOrFail($this->rejectingId);
        $property->update([
            'status' => PropertyStatus::Rejected->value,
            'rejection_reason' => $this->rejectionReason,
        ]);

        $property->owner->notify(new PropertyRejected($property));

        GenerateSitemap::dispatch();

        $this->cancelReject();

        $this->notifySuccess('Property rejected.');
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->rejectionReason = '';
        $this->resetErrorBag();
    }

    public function expireNow(int $id): void
    {
        Gate::authorize('moderate', Property::class);

        Property::findOrFail($id)->update(['status' => PropertyStatus::Expired->value]);

        GenerateSitemap::dispatch();

        $this->notifyWarning('Property expired.');
    }

    public function toggleFeatured(int $id): void
    {
        Gate::authorize('moderate', Property::class);

        $property = Property::findOrFail($id);
        $property->update(['is_featured' => ! $property->is_featured]);

        $this->notifySuccess($property->is_featured ? 'Marked as featured.' : 'Featured removed.');
    }

    public function render()
    {
        $properties = Property::query()
            ->with(['owner', 'category', 'type'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.properties.manager', [
            'properties' => $properties,
            'statuses' => PropertyStatus::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
