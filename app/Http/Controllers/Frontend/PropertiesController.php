<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Property;

class PropertiesController extends Controller
{
    public function show(Property $property)
    {
        $this->authorize('view', $property);

        $property->load(['category', 'type', 'owner', 'amenities', 'features', 'media']);

        $otherProperties = Property::query()
            ->where('id', '!=', $property->id)
            ->approved()
            ->when($property->city, fn ($q) => $q->where('city', $property->city))
            ->latest()
            ->take(4)
            ->get();

        if ($otherProperties->isEmpty()) {
            $otherProperties = Property::query()
                ->where('id', '!=', $property->id)
                ->approved()
                ->latest()
                ->take(4)
                ->get();
        }

        return view('frontend.properties.show', [
            'property' => $property,
            'otherProperties' => $otherProperties,
        ]);
    }
}
