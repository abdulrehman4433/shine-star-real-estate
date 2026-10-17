<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Projects', 'route' => 'admin.projects.index'],
        ['label' => $project->title],
    ]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="ssm-page-header__title mb-1">{{ $project->title }}</div>
            <div class="text-muted mb-2">{{ $project->society_name }}</div>
            <span class="badge {{ $project->typeEnum()->badgeClass() }}">{{ $project->typeEnum()->label() }}</span>
            @if ($project->is_featured)
                <span class="badge text-bg-warning">Featured</span>
            @endif
            @unless ($project->is_active)
                <span class="badge text-bg-secondary">Hidden</span>
            @endunless
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Basic Information</h2>
            <div class="row">
                <div class="col-md-4 mb-2"><span class="text-muted small">Developer</span><div>{{ $project->developer_name ?: '—' }}</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Contact Phone</span><div>{{ $project->contact_phone ?: '—' }}</div></div>
                <div class="col-md-4 mb-2"><span class="text-muted small">Contact Email</span><div>{{ $project->contact_email ?: '—' }}</div></div>
            </div>
            @if ($project->description)
                <hr>
                <span class="text-muted small">Description</span>
                <p class="mb-0">{{ $project->description }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Location</h2>
            <div class="row mb-2">
                <div class="col-md-8"><span class="text-muted small">Address</span><div>{{ $project->address ?: '—' }}</div></div>
                <div class="col-md-4"><span class="text-muted small">City</span><div>{{ $project->city ?: '—' }}</div></div>
            </div>

            @if ($project->lat && $project->lng)
                <div wire:ignore x-data="{
                    init() {
                        const map = L.map($refs.map).setView([{{ $project->lat }}, {{ $project->lng }}], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19,
                        }).addTo(map);
                        L.marker([{{ $project->lat }}, {{ $project->lng }}]).addTo(map);
                    }
                }">
                    <div x-ref="map" style="height: 300px;" class="rounded"></div>
                </div>
            @else
                <p class="text-muted small mb-0">No coordinates set.</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Blocks, Payment &amp; Media</h2>

            @if ($project->blocks->isEmpty())
                <p class="text-muted small mb-0">No blocks added yet.</p>
            @else
                @foreach ($project->blocks as $block)
                    <div class="mb-4 pb-3 {{ $loop->last ? '' : 'border-bottom' }}">
                        <h3 class="h6 mb-1">{{ $block->name }}</h3>
                        @if ($block->description)
                            <p class="text-muted small mb-2">{{ $block->description }}</p>
                        @endif

                        {{-- Size options (JSON), each carrying its own payment rows --}}
                        @if ($block->plot_sizes)
                            <div class="small text-muted text-uppercase mb-1">Size Options</div>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Bedrooms</th>
                                            <th>Bathrooms</th>
                                            <th>Payment Options</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($block->plot_sizes as $row)
                                            @php $options = \App\Models\ProjectBlock::paymentOptionsFor($row); @endphp
                                            <tr>
                                                <td>{{ \App\Models\ProjectBlock::sizeLabel($row) }}</td>
                                                <td>{{ ($row['category'] ?? '') ?: '—' }}</td>
                                                <td>{{ $row['bedrooms'] ?? '—' }}</td>
                                                <td>{{ $row['bathrooms'] ?? '—' }}</td>
                                                <td>
                                                    @forelse ($options as $option)
                                                        <div class="small"><span class="text-muted">{{ ($option['key'] ?: '—').' :' }}</span> <strong>{{ $option['value'] ?: '—' }}</strong></div>
                                                    @empty
                                                        <span class="text-muted">—</span>
                                                    @endforelse
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if (! $block->plot_sizes)
                            <p class="text-muted small">No size options yet.</p>
                        @endif

                        {{-- This block's own media (images / videos+links / PDFs) --}}
                        @php
                            $blockImages = $block->getMedia('block_images');
                            $blockVideos = $block->getMedia('block_videos');
                            $blockPdfs = $block->getMedia('block_pdfs');
                            $blockVideoEmbeds = $block->video_embed_urls;
                        @endphp
                        @if ($blockImages->isNotEmpty() || $blockVideos->isNotEmpty() || $blockPdfs->isNotEmpty() || $blockVideoEmbeds !== [])
                            <div class="small text-muted text-uppercase mb-1">Media</div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach ($blockImages as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank">
                                        <img src="{{ $media->getUrl() }}" class="rounded" width="110" height="75" style="object-fit: cover;">
                                    </a>
                                @endforeach
                                @foreach ($blockPdfs as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank" class="badge text-bg-light border text-decoration-none align-self-center">
                                        <i class="bi bi-file-earmark-pdf"></i> {{ $media->file_name }}
                                    </a>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($blockVideos as $media)
                                    <video src="{{ $media->getUrl() }}" controls preload="metadata" class="rounded" width="180" style="background: #000;"></video>
                                @endforeach
                            </div>
                            @if ($blockVideoEmbeds !== [])
                                <div class="row g-2 mt-1">
                                    @foreach ($blockVideoEmbeds as $embedUrl)
                                        <div class="col-md-6">
                                            <div class="ratio ratio-16x9">
                                                <iframe src="{{ $embedUrl }}" title="{{ $block->name }} video" allowfullscreen loading="lazy"></iframe>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="text-muted small mb-0">No media uploaded for this block.</p>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    @if ($project->amenities->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Amenities</h2>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($project->amenities as $amenity)
                        <span class="badge text-bg-light border">{{ $amenity->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">Media</h2>
            <div class="d-flex flex-wrap gap-2 mb-2">
                @if ($project->cover_url)
                    <img src="{{ $project->cover_thumb_url }}" class="rounded" width="160" height="107" style="object-fit: cover;" title="Cover image">
                @endif
                @foreach ($gallery as $media)
                    <img src="{{ $media->getUrl() }}" class="rounded" width="120" height="80" style="object-fit: cover;">
                @endforeach
                @if (! $project->cover_url && $gallery->isEmpty())
                    <p class="text-muted small mb-0">No media uploaded.</p>
                @endif
            </div>
            @if ($project->brochure_url)
                <a href="{{ $project->brochure_url }}" target="_blank">View Brochure (PDF)</a>
            @endif

            @if ($project->video_embed_urls || $videos->isNotEmpty())
                <div class="mt-3">
                    <span class="text-muted small d-block mb-2">Videos</span>
                    <div class="row g-2">
                        @foreach ($project->video_embed_urls as $embedUrl)
                            <div class="col-md-6">
                                <div class="ratio ratio-16x9">
                                    <iframe src="{{ $embedUrl }}" title="Project video" allowfullscreen loading="lazy"></iframe>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($videos as $media)
                            <div class="col-md-6">
                                <video src="{{ $media->getUrl() }}" controls preload="metadata"
                                    class="w-100 rounded" style="background: #000;"></video>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6">SEO</h2>
            <div class="row">
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Title</span><div>{{ $project->seo?->meta_title ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Description</span><div>{{ $project->seo?->meta_description ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Meta Keywords</span><div>{{ $project->seo?->meta_keywords ?: '—' }}</div></div>
                <div class="col-md-6 mb-2"><span class="text-muted small">Canonical URL</span><div>{{ $project->seo?->canonical_url ?: '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><span class="text-muted small">Created</span><div>{{ $project->created_at->format('M j, Y H:i') }}</div></div>
                <div class="col-md-6"><span class="text-muted small">Last Updated</span><div>{{ $project->updated_at->format('M j, Y H:i') }}</div></div>
            </div>
        </div>
    </div>
</div>
