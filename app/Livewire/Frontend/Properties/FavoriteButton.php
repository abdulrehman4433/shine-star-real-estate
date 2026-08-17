<?php

namespace App\Livewire\Frontend\Properties;

use App\Models\Property;
use Livewire\Component;

class FavoriteButton extends Component
{
    public Property $property;

    public bool $favorited = false;

    public function mount(Property $property): void
    {
        $this->property = $property;
        $this->favorited = $property->isFavoritedBy(auth()->user());
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $this->favorited = auth()->user()->toggleFavorite($this->property);
    }

    public function render()
    {
        return view('livewire.frontend.properties.favorite-button');
    }
}
