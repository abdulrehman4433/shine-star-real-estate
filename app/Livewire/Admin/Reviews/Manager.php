<?php

namespace App\Livewire\Admin\Reviews;

use App\Livewire\Concerns\Notifies;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class Manager extends Component
{
    use WithFileUploads;
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $customer_name = '';

    public string $customer_role = '';

    public int $rating = 5;

    public string $content = '';

    public bool $is_active = true;

    public $photo = null;

    public ?string $existingPhotoUrl = null;

    public function render()
    {
        return view('livewire.admin.reviews.manager', [
            'reviews' => Review::orderBy('order')->get(),
        ])->extends('admin.layouts.app')->section('content');
    }

    public function createReview(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editReview(int $id): void
    {
        $review = Review::findOrFail($id);

        $this->editingId = $review->id;
        $this->customer_name = $review->customer_name;
        $this->customer_role = (string) $review->customer_role;
        $this->rating = $review->rating;
        $this->content = $review->content;
        $this->is_active = $review->is_active;
        $this->existingPhotoUrl = $review->photo_thumb_url;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_role' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:2000',
            'photo' => 'nullable|image|max:2048',
        ]);

        $isNew = ! $this->editingId;

        $review = $this->editingId
            ? Review::findOrFail($this->editingId)
            : new Review(['order' => (Review::max('order') ?? -1) + 1]);

        $review->fill([
            'customer_name' => $this->customer_name,
            'customer_role' => $this->customer_role ?: null,
            'rating' => $this->rating,
            'content' => $this->content,
            'is_active' => $this->is_active,
        ])->save();

        if ($this->photo) {
            $review->addMedia($this->photo->getRealPath())
                ->usingFileName($this->photo->getClientOriginalName())
                ->toMediaCollection('photo');
        }

        $this->closeModal();
        Cache::forget('reviews.active');

        $this->notifySuccess($isNew ? "Review from \"{$review->customer_name}\" added." : "Review from \"{$review->customer_name}\" updated.");
    }

    public function delete(int $id): void
    {
        $review = Review::findOrFail($id);
        $name = $review->customer_name;
        $review->delete();
        Cache::forget('reviews.active');

        $this->notifySuccess("Review from \"{$name}\" deleted.");
    }

    public function toggleActive(int $id): void
    {
        $review = Review::findOrFail($id);
        $review->update(['is_active' => ! $review->is_active]);
        Cache::forget('reviews.active');

        $this->notifySuccess($review->is_active
            ? "Review from \"{$review->customer_name}\" is now visible."
            : "Review from \"{$review->customer_name}\" is now hidden.");
    }

    public function reorder(int $itemId, int $position): void
    {
        $item = Review::findOrFail($itemId);

        $siblings = Review::orderBy('order')
            ->get()
            ->reject(fn (Review $r) => $r->id === $item->id)
            ->values();

        $siblings->splice($position, 0, [$item]);

        foreach ($siblings as $index => $sibling) {
            if ($sibling->order !== $index) {
                $sibling->update(['order' => $index]);
            }
        }

        Cache::forget('reviews.active');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'customer_name', 'customer_role', 'rating', 'content', 'is_active', 'photo', 'existingPhotoUrl']);
        $this->rating = 5;
        $this->is_active = true;
        $this->resetErrorBag();
    }
}
