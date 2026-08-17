<?php

namespace App\Livewire\Frontend\Properties;

use App\Livewire\Concerns\Notifies;
use App\Livewire\Frontend\Properties\Concerns\WithPropertyFilters;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;

class MyProperties extends Component
{
    use Notifies;
    use WithPagination;
    use WithPropertyFilters;

    private const PER_PAGE = 12;

    public function delete(int $propertyId): void
    {
        $property = Property::findOrFail($propertyId);

        $this->authorize('delete', $property);

        $property->delete();

        // If this was the last item on the current page, step back so the user isn't
        // left staring at an empty page after deleting the final entry.
        $total = $this->applyFilters($this->hubQuery())->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->resetPage();
        }

        $this->notifySuccess('Property deleted.');
    }

    public function render()
    {
        $properties = $this->applyFilters($this->hubQuery())
            ->with(['category', 'type', 'media'])
            ->latest()
            ->paginate(self::PER_PAGE);

        return view('livewire.frontend.properties.my-properties', [
            'properties' => $properties,
            'categories' => $this->categories(),
            'types' => $this->types(),
        ])->extends('frontend.layouts.app')->section('content');
    }

    /** Everyone can browse every active listing; the user's own listings are always
     * included too (regardless of status) so they can edit pending/rejected ones. */
    private function hubQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Property::query()
            ->where(fn ($q) => $q->approved()->orWhere('user_id', auth()->id()));
    }
}
