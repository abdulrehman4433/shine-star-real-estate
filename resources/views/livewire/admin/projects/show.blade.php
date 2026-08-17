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
            <h2 class="h6">Blocks &amp; Size Options</h2>

            @if ($project->blocks->isEmpty() && $project->plotSizes->isEmpty())
                <p class="text-muted small mb-0">No blocks or size options added yet.</p>
            @else
                @foreach ($project->blocks as $block)
                    <div class="mb-3">
                        <h3 class="h6 mb-1">{{ $block->name }}</h3>
                        @if ($block->description)
                            <p class="text-muted small mb-2">{{ $block->description }}</p>
                        @endif

                        @if ($block->plotSizes->isEmpty())
                            <p class="text-muted small">No size options assigned to this block.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Booking</th>
                                            <th>Confirmation</th>
                                            <th>Installments</th>
                                            <th>Possession</th>
                                            <th>Notes</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($block->plotSizes as $plotSize)
                                            <tr>
                                                <td>{{ $plotSize->label }}</td>
                                                <td>{{ $plotSize->category ?: '—' }}</td>
                                                <td>{{ $plotSize->booking_amount ? number_format((float) $plotSize->booking_amount) : '—' }}</td>
                                                <td>{{ $plotSize->confirmation_amount ? number_format((float) $plotSize->confirmation_amount) : '—' }}</td>
                                                <td>
                                                    @if ($plotSize->installment_amount)
                                                        {{ number_format((float) $plotSize->installment_amount) }} x {{ $plotSize->installment_count }}
                                                        ({{ \App\Models\ProjectPlotSize::INSTALLMENT_FREQUENCIES[$plotSize->installment_frequency] ?? '—' }})
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $plotSize->possession_amount ? number_format((float) $plotSize->possession_amount) : '—' }}</td>
                                                <td>{{ $plotSize->notes ?: '—' }}</td>
                                                <td>{{ $plotSize->total_price ? number_format((float) $plotSize->total_price) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($unassignedPlotSizes->isNotEmpty())
                    <div class="mb-3">
                        <h3 class="h6 mb-1">No Specific Block</h3>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Category</th>
                                        <th>Booking</th>
                                        <th>Confirmation</th>
                                        <th>Installments</th>
                                        <th>Possession</th>
                                        <th>Notes</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($unassignedPlotSizes as $plotSize)
                                        <tr>
                                            <td>{{ $plotSize->label }}</td>
                                            <td>{{ $plotSize->category ?: '—' }}</td>
                                            <td>{{ $plotSize->booking_amount ? number_format((float) $plotSize->booking_amount) : '—' }}</td>
                                            <td>{{ $plotSize->confirmation_amount ? number_format((float) $plotSize->confirmation_amount) : '—' }}</td>
                                            <td>
                                                @if ($plotSize->installment_amount)
                                                    {{ number_format((float) $plotSize->installment_amount) }} x {{ $plotSize->installment_count }}
                                                    ({{ \App\Models\ProjectPlotSize::INSTALLMENT_FREQUENCIES[$plotSize->installment_frequency] ?? '—' }})
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $plotSize->possession_amount ? number_format((float) $plotSize->possession_amount) : '—' }}</td>
                                            <td>{{ $plotSize->notes ?: '—' }}</td>
                                            <td>{{ $plotSize->total_price ? number_format((float) $plotSize->total_price) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
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
