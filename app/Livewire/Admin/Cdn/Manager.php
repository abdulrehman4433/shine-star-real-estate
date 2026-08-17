<?php

namespace App\Livewire\Admin\Cdn;

use App\Livewire\Concerns\Notifies;
use App\Models\CdnAsset;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Manager extends Component
{
    use Notifies;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $location = 'header';

    public string $type = 'css';

    public string $url = '';

    public bool $isActive = true;

    public function createAsset(string $location = 'header'): void
    {
        $this->resetForm();
        $this->location = $location;
        $this->showModal = true;
    }

    public function editAsset(int $id): void
    {
        $asset = CdnAsset::findOrFail($id);

        $this->editingId = $asset->id;
        $this->location = $asset->location;
        $this->type = $asset->type;
        $this->url = $asset->url;
        $this->isActive = $asset->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'location' => 'required|in:header,footer',
            'type' => 'required|in:css,js,font',
            'url' => ['required', 'string', 'max:500', 'url', 'doesnt_start_with:<'],
        ], [
            'url.url' => 'Enter a plain URL only (e.g. https://cdn.example.com/style.css) — not a full <script> or <link> tag.',
            'url.doesnt_start_with' => 'Enter a plain URL only (e.g. https://cdn.example.com/style.css) — not a full <script> or <link> tag.',
        ]);

        $asset = $this->editingId
            ? CdnAsset::findOrFail($this->editingId)
            : new CdnAsset(['order' => (CdnAsset::where('location', $this->location)->max('order') ?? -1) + 1]);

        $asset->fill([
            'location' => $this->location,
            'type' => $this->type,
            'url' => $this->url,
            'is_active' => $this->isActive,
        ])->save();

        $this->closeModal();
        Cache::forget("cdn_assets.{$this->location}");
        $this->notifySuccess('CDN settings saved.');
    }

    public function delete(int $id): void
    {
        $asset = CdnAsset::findOrFail($id);
        $location = $asset->location;
        $url = $asset->url;
        $asset->delete();
        Cache::forget("cdn_assets.{$location}");
        $this->notifySuccess("\"{$url}\" deleted.");
    }

    public function toggleActive(int $id): void
    {
        $asset = CdnAsset::findOrFail($id);
        $asset->update(['is_active' => ! $asset->is_active]);
        Cache::forget("cdn_assets.{$asset->location}");
    }

    public function reorder(int $itemId, int $position, string $location): void
    {
        $item = CdnAsset::findOrFail($itemId);

        $siblings = CdnAsset::where('location', $location)
            ->orderBy('order')
            ->get()
            ->reject(fn (CdnAsset $a) => $a->id === $item->id)
            ->values();

        $siblings->splice($position, 0, [$item]);

        foreach ($siblings as $index => $sibling) {
            if ($sibling->order !== $index) {
                $sibling->update(['order' => $index]);
            }
        }

        Cache::forget("cdn_assets.{$location}");
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'url']);
        $this->location = 'header';
        $this->type = 'css';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $headerAssets = CdnAsset::inLocation('header')->orderBy('order')->get();
        $footerAssets = CdnAsset::inLocation('footer')->orderBy('order')->get();

        return view('livewire.admin.cdn.manager', [
            'headerAssets' => $headerAssets,
            'footerAssets' => $footerAssets,
        ])->extends('admin.layouts.app')->section('content');
    }
}
