<?php

namespace App\Livewire\Admin\Properties;

use App\Models\Property;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Property $property;

    public function mount(Property $property): void
    {
        Gate::authorize('moderate', Property::class);

        $this->property = $property;
    }

    public function render()
    {
        $this->property->loadMissing(['owner', 'category', 'type', 'amenities', 'features']);

        return view('livewire.admin.properties.show', [
            'property' => $this->property,
            'gallery' => $this->property->getMedia('gallery'),
        ])->extends('admin.layouts.app')->section('content');
    }
}
