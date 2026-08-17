<?php

namespace App\Livewire\Frontend\Properties;

use App\Livewire\Frontend\Properties\Concerns\WithPropertyFilters;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;

class Listing extends Component
{
    use WithPagination;
    use WithPropertyFilters;

    public function render()
    {
        $properties = $this->applyFilters(
            Property::query()->approved()->with(['category', 'type', 'media'])
        )->latest()->paginate(12);

        return view('livewire.frontend.properties.listing', [
            'properties' => $properties,
            'categories' => $this->categories(),
            'types' => $this->types(),
        ])->extends('frontend.layouts.app')->section('content');
    }
}
