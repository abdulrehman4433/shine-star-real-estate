<?php

namespace App\Livewire\Frontend\Properties;

use App\Livewire\Concerns\Notifies;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;

class MyListings extends Component
{
    use Notifies, WithPagination;

    public function delete(int $propertyId): void
    {
        $property = Property::findOrFail($propertyId);

        $this->authorize('delete', $property);

        $property->delete();

        $this->notifySuccess('Listing deleted.');
    }

    public function render()
    {
        $properties = auth()->user()
            ->properties()
            ->with(['category', 'type', 'media'])
            ->latest()
            ->paginate(10);

        return view('livewire.frontend.properties.my-listings', [
            'properties' => $properties,
        ])->extends('frontend.layouts.app')->section('content');
    }
}
