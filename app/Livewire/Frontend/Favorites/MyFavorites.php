<?php

namespace App\Livewire\Frontend\Favorites;

use App\Livewire\Concerns\Notifies;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithPagination;

class MyFavorites extends Component
{
    use Notifies, WithPagination;

    public function unfavorite(int $propertyId): void
    {
        auth()->user()->favorites()->detach($propertyId);

        $this->notifySuccess('Removed from favorites.');
    }

    public function render()
    {
        $properties = auth()->user()
            ->favorites()
            ->with(['category', 'type', 'media'])
            ->latest('favorites.created_at')
            ->paginate(12);

        return view('livewire.frontend.favorites.my-favorites', [
            'properties' => $properties,
        ])->extends('frontend.layouts.app')->section('content');
    }
}
