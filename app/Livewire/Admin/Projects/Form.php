<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\ProjectType;
use App\Livewire\Concerns\Notifies;
use App\Models\Project;
use App\Models\ProjectPlotSize;
use App\Models\PropertyAmenity;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use Notifies, WithFileUploads;

    #[Locked]
    public ?int $projectId = null;

    public string $title = '';

    public string $societyName = '';

    public string $developerName = '';

    public string $type = 'residential';

    public string $description = '';

    public string $address = '';

    public string $city = '';

    public ?float $lat = null;

    public ?float $lng = null;

    public string $contactPhone = '';

    public string $contactEmail = '';

    public string $videoUrl = '';

    public bool $isFeatured = false;

    public bool $isActive = true;

    public array $selectedAmenities = [];

    public array $blocks = [];

    public array $plotSizes = [];

    public $coverImage = null;

    public array $galleryImages = [];

    public $brochure = null;

    public string $seoMetaTitle = '';

    public string $seoMetaDescription = '';

    public string $seoMetaKeywords = '';

    public string $seoCanonicalUrl = '';

    public function mount(?Project $project = null): void
    {
        if ($project && $project->exists) {
            $this->projectId = $project->id;
            $this->title = $project->title;
            $this->societyName = $project->society_name;
            $this->developerName = (string) $project->developer_name;
            $this->type = $project->type;
            $this->description = (string) $project->description;
            $this->address = (string) $project->address;
            $this->city = (string) $project->city;
            $this->lat = $project->lat ? (float) $project->lat : null;
            $this->lng = $project->lng ? (float) $project->lng : null;
            $this->contactPhone = (string) $project->contact_phone;
            $this->contactEmail = (string) $project->contact_email;
            $this->videoUrl = (string) $project->video_url;
            $this->isFeatured = $project->is_featured;
            $this->isActive = $project->is_active;
            $this->selectedAmenities = $project->amenities->pluck('id')->map(fn ($id) => (string) $id)->all();

            $blocks = $project->blocks;
            $this->blocks = $blocks->map(fn ($b) => [
                'name' => $b->name,
                'description' => (string) $b->description,
            ])->all();

            $blockIndexById = [];
            foreach ($blocks as $index => $block) {
                $blockIndexById[$block->id] = $index;
            }

            $this->plotSizes = $project->plotSizes->map(fn ($p) => [
                'block_index' => $p->project_block_id !== null && isset($blockIndexById[$p->project_block_id])
                    ? (string) $blockIndexById[$p->project_block_id]
                    : '',
                'size_value' => (string) $p->size_value,
                'unit' => $p->unit,
                'category' => (string) $p->category,
                'bedrooms' => (string) $p->bedrooms,
                'bathrooms' => (string) $p->bathrooms,
                'total_price' => (string) $p->total_price,
                'booking_amount' => (string) $p->booking_amount,
                'confirmation_amount' => (string) $p->confirmation_amount,
                'installment_amount' => (string) $p->installment_amount,
                'installment_count' => (string) $p->installment_count,
                'installment_frequency' => (string) $p->installment_frequency,
                'possession_amount' => (string) $p->possession_amount,
                'notes' => (string) $p->notes,
            ])->all();

            $this->seoMetaTitle = (string) $project->seo?->meta_title;
            $this->seoMetaDescription = (string) $project->seo?->meta_description;
            $this->seoMetaKeywords = (string) $project->seo?->meta_keywords;
            $this->seoCanonicalUrl = (string) $project->seo?->canonical_url;
        }

        if (empty($this->blocks)) {
            $this->blocks = [['name' => '', 'description' => '']];
        }

        if (empty($this->plotSizes)) {
            $this->plotSizes = [$this->emptyPlotSizeRow()];
        }
    }

    private function emptyPlotSizeRow(): array
    {
        return [
            'block_index' => '',
            'size_value' => '',
            'unit' => 'marla',
            'category' => '',
            'bedrooms' => '',
            'bathrooms' => '',
            'total_price' => '',
            'booking_amount' => '',
            'confirmation_amount' => '',
            'installment_amount' => '',
            'installment_count' => '',
            'installment_frequency' => '',
            'possession_amount' => '',
            'notes' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'societyName' => 'required|string|max:255',
            'developerName' => 'nullable|string|max:255',
            'type' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, ProjectType::cases())),
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'contactPhone' => 'nullable|string|max:50',
            'contactEmail' => 'nullable|email|max:255',
            'videoUrl' => 'nullable|url|max:255',
            'coverImage' => 'nullable|image|max:4096',
            'galleryImages.*' => 'nullable|image|max:4096',
            'brochure' => 'nullable|mimes:pdf|max:10240',
            'selectedAmenities' => 'array',
            'selectedAmenities.*' => 'exists:property_amenities,id',
            'blocks.*.name' => 'nullable|string|max:255',
            'blocks.*.description' => 'nullable|string|max:1000',
            'plotSizes.*.block_index' => 'nullable|string',
            'plotSizes.*.size_value' => 'nullable|numeric|min:0',
            'plotSizes.*.unit' => 'nullable|in:marla,kanal,sqft',
            'plotSizes.*.category' => 'nullable|string|max:255',
            'plotSizes.*.bedrooms' => 'nullable|integer|min:0|max:20',
            'plotSizes.*.bathrooms' => 'nullable|integer|min:0|max:20',
            'plotSizes.*.total_price' => 'nullable|numeric|min:0',
            'plotSizes.*.booking_amount' => 'nullable|numeric|min:0',
            'plotSizes.*.confirmation_amount' => 'nullable|numeric|min:0',
            'plotSizes.*.installment_amount' => 'nullable|numeric|min:0',
            'plotSizes.*.installment_count' => 'nullable|integer|min:0',
            'plotSizes.*.installment_frequency' => 'nullable|in:monthly,quarterly,half_yearly,yearly',
            'plotSizes.*.possession_amount' => 'nullable|numeric|min:0',
            'plotSizes.*.notes' => 'nullable|string|max:1000',
            'seoMetaTitle' => 'nullable|string|max:255',
            'seoMetaDescription' => 'nullable|string|max:255',
            'seoMetaKeywords' => 'nullable|string|max:255',
            'seoCanonicalUrl' => 'nullable|string|max:255',
        ];
    }

    public function setLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function addBlockRow(): void
    {
        $this->blocks[] = ['name' => '', 'description' => ''];
    }

    public function removeBlockRow(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);

        foreach ($this->plotSizes as $i => $row) {
            if ($row['block_index'] === '') {
                continue;
            }

            if ((int) $row['block_index'] === $index) {
                $this->plotSizes[$i]['block_index'] = '';
            } elseif ((int) $row['block_index'] > $index) {
                $this->plotSizes[$i]['block_index'] = (string) ((int) $row['block_index'] - 1);
            }
        }
    }

    public function addPlotSizeRow(): void
    {
        $this->plotSizes[] = $this->emptyPlotSizeRow();
    }

    public function removePlotSizeRow(int $index): void
    {
        unset($this->plotSizes[$index]);
        $this->plotSizes = array_values($this->plotSizes);
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $project = Project::findOrFail($this->projectId);
        $project->media()->where('id', $mediaId)->where('collection_name', 'gallery')->first()?->delete();
    }

    public function save()
    {
        $this->validate();

        $project = $this->projectId
            ? Project::findOrFail($this->projectId)
            : new Project(['order' => (Project::max('order') ?? -1) + 1]);

        $project->fill([
            'title' => $this->title,
            'society_name' => $this->societyName,
            'developer_name' => $this->developerName ?: null,
            'type' => $this->type,
            'description' => $this->description ?: null,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'contact_phone' => $this->contactPhone ?: null,
            'contact_email' => $this->contactEmail ?: null,
            'video_url' => $this->videoUrl ?: null,
            'is_featured' => $this->isFeatured,
            'is_active' => $this->isActive,
        ])->save();

        $project->amenities()->sync($this->selectedAmenities);

        // Plot sizes reference blocks by FK, so clear both, recreate blocks first,
        // then remap each plot size's local row-index back to a real block_id.
        $project->plotSizes()->delete();
        $project->blocks()->delete();

        $blockIdByIndex = [];
        foreach ($this->blocks as $index => $block) {
            if (trim($block['name'] ?? '') === '') {
                continue;
            }

            $created = $project->blocks()->create([
                'name' => $block['name'],
                'description' => $block['description'] ?: null,
                'order' => $index,
            ]);

            $blockIdByIndex[$index] = $created->id;
        }

        foreach ($this->plotSizes as $index => $row) {
            if ($row['size_value'] === '' || $row['size_value'] === null) {
                continue;
            }

            $project->plotSizes()->create([
                'project_block_id' => $row['block_index'] !== '' ? ($blockIdByIndex[(int) $row['block_index']] ?? null) : null,
                'size_value' => $row['size_value'],
                'unit' => $row['unit'] ?: 'marla',
                'category' => $row['category'] ?: null,
                'bedrooms' => $row['bedrooms'] ?: null,
                'bathrooms' => $row['bathrooms'] ?: null,
                'total_price' => $row['total_price'] ?: null,
                'booking_amount' => $row['booking_amount'] ?: null,
                'confirmation_amount' => $row['confirmation_amount'] ?: null,
                'installment_amount' => $row['installment_amount'] ?: null,
                'installment_count' => $row['installment_count'] ?: null,
                'installment_frequency' => $row['installment_frequency'] ?: null,
                'possession_amount' => $row['possession_amount'] ?: null,
                'notes' => $row['notes'] ?: null,
                'order' => $index,
            ]);
        }

        if ($this->coverImage) {
            $project->addMedia($this->coverImage->getRealPath())
                ->usingFileName($this->coverImage->getClientOriginalName())
                ->toMediaCollection('cover');
        }

        foreach ($this->galleryImages as $image) {
            $project->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery');
        }

        if ($this->brochure) {
            $project->addMedia($this->brochure->getRealPath())
                ->usingFileName($this->brochure->getClientOriginalName())
                ->toMediaCollection('brochure');
        }

        $project->updateSeo([
            'meta_title' => $this->seoMetaTitle ?: null,
            'meta_description' => $this->seoMetaDescription ?: null,
            'meta_keywords' => $this->seoMetaKeywords ?: null,
            'canonical_url' => $this->seoCanonicalUrl ?: null,
        ]);

        $this->projectId = $project->id;

        $this->flashSuccess('Project saved.');

        return redirect()->route('admin.projects.index');
    }

    public function render()
    {
        return view('livewire.admin.projects.form', [
            'amenities' => PropertyAmenity::query()->active()->orderBy('order')->get(),
            'existingGallery' => $this->projectId ? Project::find($this->projectId)?->getMedia('gallery') : collect(),
            'existingCoverUrl' => $this->projectId ? Project::find($this->projectId)?->cover_thumb_url : null,
            'existingBrochureUrl' => $this->projectId ? Project::find($this->projectId)?->brochure_url : null,
            'types' => ProjectType::cases(),
            'units' => ProjectPlotSize::UNITS,
            'installmentFrequencies' => ProjectPlotSize::INSTALLMENT_FREQUENCIES,
        ])->extends('admin.layouts.app')->section('content');
    }
}
