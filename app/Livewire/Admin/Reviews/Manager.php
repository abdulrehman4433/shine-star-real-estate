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

    // Captured the instant Livewire stores the temp upload (see updatedPhoto()). Reading the name
    // lazily inside save() is what produced 40-char hashes like "993f90d5...jpg": by then the
    // {tmp}.json sidecar holding the real filename can already be unavailable, and Spatie then
    // silently falls back to the temp file's own name.
    public string $photoOriginalName = '';

    public ?string $existingPhotoUrl = null;

    public function render()
    {
        return view('livewire.admin.reviews.manager', [
            'reviews' => Review::orderBy('order')->get(),
            'photoPreviewUrl' => $this->photoPreviewUrl(),
        ])->extends('admin.layouts.app')->section('content');
    }

    /** Livewire's own preview for the file currently staged in $photo, so the admin sees what is
     *  about to replace the current picture rather than an unverifiable filename. */
    private function photoPreviewUrl(): ?string
    {
        try {
            return $this->photo && $this->photo->isPreviewable()
                ? $this->photo->temporaryUrl()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Fires the moment Livewire persists the temp upload — the only point where the user's real
     *  filename is reliably readable (see photoFileName()). */
    public function updatedPhoto(): void
    {
        $this->photoOriginalName = $this->photo
            ? trim((string) $this->photo->getClientOriginalName())
            : '';
    }

    public function createReview(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editReview(int $id): void
    {
        $review = Review::findOrFail($id);

        // createReview() goes through resetForm() but this method never did, so a file picked for
        // one review (typically left over after a failed validation) could be silently saved onto
        // whatever review was opened next. Start every edit from a clean upload state.
        $this->photo = null;
        $this->photoOriginalName = '';
        $this->resetErrorBag();

        $this->editingId = $review->id;
        $this->customer_name = $review->customer_name;
        $this->customer_role = (string) $review->customer_role;
        $this->rating = $review->rating;
        $this->content = $review->content;
        $this->is_active = $review->is_active;
        // Full-size original, not the 120px thumb conversion — the modal is the only place the admin
        // can actually check which picture they're about to replace.
        $this->existingPhotoUrl = $review->photo_url ?: $review->photo_thumb_url;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'customer_name' => 'required|string|max:255',
            'customer_role' => 'nullable|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:2000',
            // 10240 KB matches config('media-library.max_file_size') — the old 2048 rejected files
            // the media library would have accepted.
            'photo' => 'nullable|image|max:10240',
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

        $photoFailed = false;

        try {
            // Guarded on a file actually being chosen: a text-only edit must never touch the media
            // collection, which is what keeps an existing picture intact.
            if ($this->photo) {
                $review->addMedia($this->photo->getRealPath())
                    ->usingFileName($this->photoFileName())
                    ->toMediaCollection('photo');

                // Drop the reference immediately (as Headers\Manager does with $logoUpload) instead
                // of relying on closeModal() happening to run later — Spatie has already consumed
                // the temp file, so leaving it staged would break a subsequent save.
                $this->photo = null;
                $this->photoOriginalName = '';
            }
        } catch (\Throwable $e) {
            $photoFailed = true;
            report($e);
        } finally {
            // The row is already saved above, so the homepage cache must be invalidated however the
            // image step went — otherwise the section keeps serving the previous version for the
            // full hour of Cache::remember('reviews.active').
            Cache::forget('reviews.active');
        }

        if ($photoFailed) {
            // Keep the modal open so the photo can be re-selected and retried.
            $this->existingPhotoUrl = $review->fresh()?->photo_url ?: $review->photo_thumb_url;
            $this->addError('photo', 'The review was saved, but its photo could not be stored. Please re-select the image and save again.');

            return;
        }

        $this->closeModal();

        $this->notifySuccess($isNew ? "Review from \"{$review->customer_name}\" added." : "Review from \"{$review->customer_name}\" updated.");
    }

    /** Explicitly clears this review's photo. The only path that ever calls clearMediaCollection(),
     *  so nothing can remove a picture unless the admin clicks this behind a confirm prompt — and
     *  DefaultFileRemover only deletes files whose basename matches this media's file_name, so
     *  sibling files and every other review's photo are left alone. */
    public function removePhoto(): void
    {
        if (! $this->editingId) {
            return;
        }

        $review = Review::findOrFail($this->editingId);

        $review->clearMediaCollection('photo');

        $this->photo = null;
        $this->photoOriginalName = '';
        $this->existingPhotoUrl = null;
        $this->resetErrorBag('photo');

        Cache::forget('reviews.active');

        $this->notifySuccess("Photo removed from \"{$review->customer_name}\".");
    }

    /**
     * A real, readable filename for the stored media — never Livewire's temp hash.
     *
     * TemporaryUploadedFile::getClientOriginalName() reads a `{tmp}.json` sidecar and, when that
     * isn't there, resolves to an empty/garbled value. Spatie treats an empty usingFileName() as
     * "not set" and falls back to TemporaryUploadedFile::getFilename() — the temp file's own
     * 40-char hash — which is how media 99 came to be stored as `993f90d5...jpg`.
     */
    private function photoFileName(): string
    {
        $photo = $this->photo;

        $name = $this->photoOriginalName;

        if ($name === '' || $this->looksLikeTempHash($name)) {
            $name = trim((string) $photo->getClientOriginalName());
        }

        // Last resort: read the sidecar ourselves, straight off disk.
        if ($name === '' || $this->looksLikeTempHash($name)) {
            $sidecar = $photo->getPathname().'.json';

            if (is_file($sidecar)) {
                $decoded = json_decode((string) file_get_contents($sidecar), true);
                $candidate = is_array($decoded) ? trim((string) ($decoded['name'] ?? '')) : '';

                if ($candidate !== '' && ! $this->looksLikeTempHash($candidate)) {
                    $name = $candidate;
                }
            }
        }

        // Basename only: a client-supplied name must never be able to write outside its own
        // media directory, and Spatie does not strip traversal out of usingFileName().
        $name = basename(str_replace('\\', '/', $name));

        if ($name === '' || $this->looksLikeTempHash($name)) {
            // Derive the extension from the temp file itself, whose suffix Livewire took from the
            // real upload, rather than from getClientOriginalExtension() (same broken lookup).
            $extension = strtolower(pathinfo($photo->getFilename(), PATHINFO_EXTENSION) ?: 'jpg');
            $name = 'review-'.now()->format('Ymd-His').'.'.$extension;
        }

        return $name;
    }

    /** Str::random(40) + extension — TemporaryUploadedFile::generateHashName()'s output. */
    private function looksLikeTempHash(string $name): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}\.[a-z0-9]+$/i', $name);
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
        $this->reset(['editingId', 'customer_name', 'customer_role', 'rating', 'content', 'is_active', 'photo', 'photoOriginalName', 'existingPhotoUrl']);
        $this->rating = 5;
        $this->is_active = true;
        $this->resetErrorBag();
    }
}
