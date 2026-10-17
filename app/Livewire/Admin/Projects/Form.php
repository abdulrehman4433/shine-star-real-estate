<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\ProjectType;
use App\Livewire\Concerns\Notifies;
use App\Models\Project;
use App\Models\ProjectBlock;
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

    /** Repeatable rows of external video links (YouTube/Vimeo share URLs). */
    public array $videoLinks = [];

    /** Newly attached video files (Spatie temporary uploads, saved into the 'videos' collection). */
    public array $videoFiles = [];

    public bool $isFeatured = false;

    public bool $isActive = true;

    public array $selectedAmenities = [];

    /** Each row: ['id' => int|null, 'name', 'description',
     *  'plot_sizes' => [[size_value, unit, category, bedrooms, bathrooms,
     *                    'payment_options' => [[key, value], ...]], ...]] */
    public array $blocks = [];

    /** Staged block media keyed by block row index: blockImages[i][] = TemporaryUploadedFile.
     *  Attached to the persisted block during save(). */
    public array $blockImages = [];

    public array $blockVideos = [];

    public array $blockPdfs = [];

    /** External video links per block row index: blockVideoUrls[i][] = url string. */
    public array $blockVideoUrls = [];

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
            $this->videoLinks = $project->videoLinks();
            $this->isFeatured = $project->is_featured;
            $this->isActive = $project->is_active;
            $this->selectedAmenities = $project->amenities->pluck('id')->map(fn ($id) => (string) $id)->all();

            $this->blocks = [];
            $this->blockVideoUrls = [];

            foreach ($project->blocks as $block) {
                $this->blocks[] = [
                    'id' => $block->id,
                    'name' => $block->name,
                    'description' => (string) $block->description,
                    'plot_sizes' => collect($block->plot_sizes ?? [])->map(fn ($row) => [
                        'size_value' => (string) ($row['size_value'] ?? ''),
                        'unit' => $row['unit'] ?? 'marla',
                        'category' => (string) ($row['category'] ?? ''),
                        'bedrooms' => (string) ($row['bedrooms'] ?? ''),
                        'bathrooms' => (string) ($row['bathrooms'] ?? ''),
                        'payment_options' => collect($row['payment_options'] ?? [])->map(fn ($option) => [
                            'key' => (string) ($option['key'] ?? ''),
                            'value' => (string) ($option['value'] ?? ''),
                        ])->values()->all(),
                    ])->values()->all(),
                ];

                $links = $block->videoLinkRows();
                $this->blockVideoUrls[] = $links === [] ? [''] : $links;
            }

            $this->seoMetaTitle = (string) $project->seo?->meta_title;
            $this->seoMetaDescription = (string) $project->seo?->meta_description;
            $this->seoMetaKeywords = (string) $project->seo?->meta_keywords;
            $this->seoCanonicalUrl = (string) $project->seo?->canonical_url;
        }

        if (empty($this->videoLinks)) {
            $this->videoLinks = [''];
        }

        if (empty($this->blocks)) {
            $this->blocks = [$this->emptyBlockRow()];
            // One row per block, each row an array of URL slots — never a flat string list.
            $this->blockVideoUrls = [['']];
        }
    }

    private function emptyBlockRow(): array
    {
        return [
            'id' => null,
            'name' => '',
            'description' => '',
            'plot_sizes' => [],
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
            'videoLinks' => 'array',
            'videoLinks.*' => 'nullable|url|max:255',
            'videoFiles' => 'array',
            'videoFiles.*' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/x-ms-wmv|max:51200',
            'coverImage' => 'nullable|image|max:4096',
            'galleryImages.*' => 'nullable|image|max:4096',
            'brochure' => 'nullable|mimes:pdf|max:10240',
            'selectedAmenities' => 'array',
            'selectedAmenities.*' => 'exists:property_amenities,id',
            'blocks' => 'array',
            'blocks.*.id' => 'nullable|integer',
            'blocks.*.name' => 'nullable|string|max:255',
            'blocks.*.description' => 'nullable|string|max:1000',
            'blocks.*.plot_sizes' => 'array',
            'blocks.*.plot_sizes.*.size_value' => 'nullable|numeric|min:0',
            'blocks.*.plot_sizes.*.unit' => 'nullable|in:marla,kanal,sqft',
            'blocks.*.plot_sizes.*.category' => 'nullable|string|max:255',
            'blocks.*.plot_sizes.*.bedrooms' => 'nullable|integer|min:0|max:20',
            'blocks.*.plot_sizes.*.bathrooms' => 'nullable|integer|min:0|max:20',
            'blocks.*.plot_sizes.*.payment_options' => 'array',
            'blocks.*.plot_sizes.*.payment_options.*.key' => 'nullable|string|max:255',
            'blocks.*.plot_sizes.*.payment_options.*.value' => 'nullable|string|max:255',
            'blockVideoUrls' => 'array',
            'blockVideoUrls.*' => 'array',
            'blockVideoUrls.*.*' => 'nullable|url|max:255',
            'blockImages' => 'array',
            'blockImages.*' => 'array',
            'blockImages.*.*' => 'nullable|image|max:4096',
            'blockVideos' => 'array',
            'blockVideos.*' => 'array',
            'blockVideos.*.*' => 'required|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime,video/x-msvideo,video/x-ms-wmv|max:51200',
            'blockPdfs' => 'array',
            'blockPdfs.*' => 'array',
            'blockPdfs.*.*' => 'required|file|mimes:pdf|max:10240',
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
        $this->blocks[] = $this->emptyBlockRow();
        $this->blockVideoUrls[] = [''];
    }

    public function removeBlockRow(int $index): void
    {
        unset($this->blocks[$index]);
        $this->blocks = array_values($this->blocks);

        // Keep the staged-media arrays (and video-link rows) in step with the block rows they belong to.
        foreach (['blockImages', 'blockVideos', 'blockPdfs', 'blockVideoUrls'] as $property) {
            unset($this->{$property}[$index]);
            $this->{$property} = array_values($this->{$property});
        }
    }

    public function addPlotSizeRow(int $blockIndex): void
    {
        $this->blocks[$blockIndex]['plot_sizes'][] = [
            'size_value' => '',
            'unit' => 'marla',
            'category' => '',
            'bedrooms' => '',
            'bathrooms' => '',
            'payment_options' => [],
        ];
    }

    public function removePlotSizeRow(int $blockIndex, int $rowIndex): void
    {
        unset($this->blocks[$blockIndex]['plot_sizes'][$rowIndex]);
        $this->blocks[$blockIndex]['plot_sizes'] = array_values($this->blocks[$blockIndex]['plot_sizes']);
    }

    /** Add one dynamic key/value row to ONE size option (payment is per-size). */
    public function addSizePaymentRow(int $blockIndex, int $sizeIndex): void
    {
        $this->blocks[$blockIndex]['plot_sizes'][$sizeIndex]['payment_options'] ??= [];
        $this->blocks[$blockIndex]['plot_sizes'][$sizeIndex]['payment_options'][] = ['key' => '', 'value' => ''];
    }

    public function removeSizePaymentRow(int $blockIndex, int $sizeIndex, int $optionIndex): void
    {
        unset($this->blocks[$blockIndex]['plot_sizes'][$sizeIndex]['payment_options'][$optionIndex]);
        $this->blocks[$blockIndex]['plot_sizes'][$sizeIndex]['payment_options'] = array_values($this->blocks[$blockIndex]['plot_sizes'][$sizeIndex]['payment_options']);
    }

    public function addBlockVideoUrlRow(int $blockIndex): void
    {
        $this->blockVideoUrls[$blockIndex][] = '';
    }

    public function removeBlockVideoUrlRow(int $blockIndex, int $urlIndex): void
    {
        unset($this->blockVideoUrls[$blockIndex][$urlIndex]);
        $this->blockVideoUrls[$blockIndex] = array_values($this->blockVideoUrls[$blockIndex]);
    }

    public function removeBlockMedia(int $blockId, int $mediaId): void
    {
        $block = $this->projectId
            ? Project::find($this->projectId)?->blocks()->whereKey($blockId)->first()
            : null;

        $block?->media()
            ->where('id', $mediaId)
            ->where('collection_name', 'like', 'block_%')
            ->first()?->delete();
    }

    public function removePendingBlockFile(int $blockIndex, int $fileIndex, string $kind): void
    {
        $property = ['images' => 'blockImages', 'videos' => 'blockVideos', 'pdfs' => 'blockPdfs'][$kind] ?? null;

        if ($property === null || ! isset($this->{$property}[$blockIndex][$fileIndex])) {
            return;
        }

        unset($this->{$property}[$blockIndex][$fileIndex]);
        $this->{$property}[$blockIndex] = array_values($this->{$property}[$blockIndex]);
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $project = Project::findOrFail($this->projectId);
        $project->media()->where('id', $mediaId)->where('collection_name', 'gallery')->first()?->delete();
    }

    public function addVideoLinkRow(): void
    {
        $this->videoLinks[] = '';
    }

    public function removeVideoLinkRow(int $index): void
    {
        unset($this->videoLinks[$index]);
        $this->videoLinks = array_values($this->videoLinks);
    }

    public function removeExistingVideo(int $mediaId): void
    {
        $project = Project::findOrFail($this->projectId);
        $project->media()->where('id', $mediaId)->where('collection_name', 'videos')->first()?->delete();
    }

    public function save()
    {
        $this->validate();

        // Trim + drop empty repeater rows; the cleaned list drives both the multi-value
        // video_urls column and the legacy video_url column (kept as "the first link"
        // so anything still reading the single column stays correct).
        $videoLinks = array_values(array_filter(
            array_map(fn ($url) => trim((string) $url), $this->videoLinks),
            fn ($url) => $url !== '',
        ));

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
            'video_url' => $videoLinks[0] ?? null,
            'video_urls' => $videoLinks,
            'is_featured' => $this->isFeatured,
            'is_active' => $this->isActive,
        ])->save();

        $project->amenities()->sync($this->selectedAmenities);

        // Blocks are UPSERTED — not deleted-and-recreated like the old design — because each
        // block now owns media files and its id must survive an unrelated edit. Rows removed
        // from the form are deleted individually below so every model's deleting event fires
        // (a bulk ->delete() would skip Spatie's per-model media cleanup and orphan files).
        $incomingBlockIds = [];

        foreach ($this->blocks as $index => $row) {
            if (trim($row['name'] ?? '') === '') {
                continue;
            }

            $attributes = [
                'name' => $row['name'],
                'description' => ($row['description'] ?? '') ?: null,
                'order' => $index,
                'plot_sizes' => $this->cleanPlotSizes($row['plot_sizes'] ?? []),
                'video_urls' => array_values(array_filter(
                    array_map(fn ($url) => trim((string) $url), $this->blockVideoUrls[$index] ?? []),
                    fn ($url) => $url !== '',
                )),
            ];

            $block = ! empty($row['id']) ? $project->blocks()->whereKey($row['id'])->first() : null;

            if ($block) {
                $block->update($attributes);
            } else {
                $block = $project->blocks()->create($attributes);
            }

            $incomingBlockIds[] = $block->id;

            foreach (['blockImages' => 'block_images', 'blockVideos' => 'block_videos', 'blockPdfs' => 'block_pdfs'] as $property => $collection) {
                foreach ($this->{$property}[$index] ?? [] as $file) {
                    $block->addMedia($file->getRealPath())
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection($collection);
                }

                $this->{$property}[$index] = [];
            }
        }

        $project->blocks()
            ->when($incomingBlockIds, fn ($query) => $query->whereNotIn('id', $incomingBlockIds))
            ->get()
            ->each->delete();

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

        foreach ($this->videoFiles as $video) {
            $project->addMedia($video->getRealPath())
                ->usingFileName($video->getClientOriginalName())
                ->toMediaCollection('videos');
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

    /** Drop empty size rows; keep only known fields/types so stale keys never reach the JSON column.
     *  Each size carries its own cleaned dynamic key/value payment rows. */
    private function cleanPlotSizes(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => ($row['size_value'] ?? '') !== '' && $row['size_value'] !== null)
            ->map(fn ($row) => [
                'size_value' => (float) $row['size_value'],
                'unit' => in_array($row['unit'] ?? '', array_keys(ProjectBlock::UNITS), true) ? $row['unit'] : 'marla',
                'category' => ($row['category'] ?? '') !== '' ? $row['category'] : null,
                'bedrooms' => ($row['bedrooms'] ?? '') !== '' ? (int) $row['bedrooms'] : null,
                'bathrooms' => ($row['bathrooms'] ?? '') !== '' ? (int) $row['bathrooms'] : null,
                'payment_options' => $this->cleanPaymentOptions($row['payment_options'] ?? []),
            ])
            ->values()
            ->all();
    }

    /** Keep rows that have at least one non-empty side; fully-empty rows are just add-row spacers. */
    private function cleanPaymentOptions(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => trim((string) ($row['key'] ?? '')) !== '' || trim((string) ($row['value'] ?? '')) !== '')
            ->map(fn ($row) => [
                'key' => trim((string) ($row['key'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        $project = $this->projectId ? Project::find($this->projectId) : null;

        $blockMedia = collect();
        if ($project) {
            $blockMedia = $project->blocks->mapWithKeys(fn ($b) => [
                $b->id => [
                    'images' => $b->getMedia('block_images'),
                    'videos' => $b->getMedia('block_videos'),
                    'pdfs' => $b->getMedia('block_pdfs'),
                ],
            ]);
        }

        return view('livewire.admin.projects.form', [
            'amenities' => PropertyAmenity::query()->active()->orderBy('order')->get(),
            'existingGallery' => $this->projectId ? Project::find($this->projectId)?->getMedia('gallery') : collect(),
            'existingCoverUrl' => $this->projectId ? Project::find($this->projectId)?->cover_thumb_url : null,
            'existingBrochureUrl' => $this->projectId ? Project::find($this->projectId)?->brochure_url : null,
            'existingVideos' => $this->projectId ? Project::find($this->projectId)?->getMedia('videos') : collect(),
            'blockMedia' => $blockMedia,
            'types' => ProjectType::cases(),
            'units' => ProjectBlock::UNITS,
        ])->extends('admin.layouts.app')->section('content');
    }
}
