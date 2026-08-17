<div class="container-fluid py-4" style="max-width: 1100px;">
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Projects', 'route' => 'admin.projects.index'],
        ['label' => $projectId ? 'Edit Project' : 'Add Project'],
    ]])

    <div class="ssm-page-header">
        <div class="ssm-page-header__title">{{ $projectId ? 'Edit Project' : 'Add Project' }}</div>
    </div>

    <form wire:submit="save">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-info-circle"></i> Basic Information</h2>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Name</label>
                        <input type="text" wire:model="title" placeholder="e.g. Bahria Town Phase 8" class="form-control @error('title') is-invalid @enderror">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Society Name</label>
                        <input type="text" wire:model="societyName" class="form-control @error('societyName') is-invalid @enderror">
                        @error('societyName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Developer (optional)</label>
                        <input type="text" wire:model="developerName" class="form-control @error('developerName') is-invalid @enderror">
                        @error('developerName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Type</label>
                        <select wire:model="type" class="form-select @error('type') is-invalid @enderror">
                            @foreach ($types as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea wire:model="description" rows="5" class="form-control @error('description') is-invalid @enderror"></textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-geo-alt"></i> Location</h2>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" wire:model="address" class="form-control @error('address') is-invalid @enderror">
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" wire:model="city" class="form-control @error('city') is-invalid @enderror">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <p class="form-text mb-2">Click on the map to drop a pin at the project's location.</p>

                <div
                    wire:ignore
                    x-data="{
                        map: null,
                        marker: null,
                        init() {
                            const startLat = {{ $lat ?? 20 }};
                            const startLng = {{ $lng ?? 0 }};
                            const startZoom = {{ $lat && $lng ? 13 : 2 }};

                            this.map = L.map($refs.map).setView([startLat, startLng], startZoom);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; OpenStreetMap contributors',
                                maxZoom: 19,
                            }).addTo(this.map);

                            @if ($lat && $lng)
                                this.marker = L.marker([{{ $lat }}, {{ $lng }}]).addTo(this.map);
                            @endif

                            this.map.on('click', (e) => {
                                if (this.marker) {
                                    this.marker.setLatLng(e.latlng);
                                } else {
                                    this.marker = L.marker(e.latlng).addTo(this.map);
                                }

                                $wire.setLocation(e.latlng.lat, e.latlng.lng);
                            });
                        }
                    }"
                >
                    <div x-ref="map" style="height: 320px;" class="rounded mb-2"></div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <span class="form-text">Latitude: {{ $lat ?? '—' }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="form-text">Longitude: {{ $lng ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                    <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-grid-3x3-gap"></i> Blocks</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addBlockRow">
                        <i class="bi bi-plus-lg"></i> Add Block
                    </button>
                </div>
                <p class="form-text">If the society is divided into blocks or precincts (e.g. Block A, Precinct 5), list them here — you'll be able to assign each size option below to one of them.</p>

                @foreach ($blocks as $index => $block)
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <input type="text" placeholder="Block name (e.g. Block A)"
                                wire:model="blocks.{{ $index }}.name" class="form-control @error('blocks.'.$index.'.name') is-invalid @enderror">
                            @error('blocks.'.$index.'.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-7">
                            <input type="text" placeholder="Description (optional)"
                                wire:model="blocks.{{ $index }}.description" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100" wire:click="removeBlockRow({{ $index }})" title="Remove block">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center ssm-form-section-title">
                    <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-rulers"></i> Payment Plans</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addPlotSizeRow">
                        <i class="bi bi-plus-lg"></i> Add Size Option
                    </button>
                </div>
                <p class="form-text">Add each plot/area size offered (e.g. 5 Marla, 10 Marla, 1 Kanal), the block it belongs to, and its payment plan.</p>

                @foreach ($plotSizes as $index => $row)
                    <div class="border rounded-3 p-3 mb-3 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <strong class="text-muted small">Size Option {{ $index + 1 }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removePlotSizeRow({{ $index }})">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small">Size</label>
                                <input type="number" step="0.01" placeholder="e.g. 5"
                                    wire:model="plotSizes.{{ $index }}.size_value" class="form-control @error('plotSizes.'.$index.'.size_value') is-invalid @enderror">
                                @error('plotSizes.'.$index.'.size_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Unit</label>
                                <select wire:model="plotSizes.{{ $index }}.unit" class="form-select">
                                    @foreach ($units as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Block</label>
                                <select wire:model="plotSizes.{{ $index }}.block_index" class="form-select">
                                    <option value="">No specific block</option>
                                    @foreach ($blocks as $bIndex => $block)
                                        <option value="{{ $bIndex }}">{{ $block['name'] !== '' ? $block['name'] : 'Block '.($bIndex + 1).' (unnamed)' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Category (optional)</label>
                                <input type="text" placeholder="e.g. Residential Plot, Villa, Apartment, Commercial Shop"
                                    wire:model="plotSizes.{{ $index }}.category" class="form-control">
                            </div>
                        </div>

                        <hr class="my-3">
                        <div class="small text-muted mb-2">Payment Plan</div>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Total Price</label>
                                <input type="number" step="0.01" wire:model="plotSizes.{{ $index }}.total_price" class="form-control @error('plotSizes.'.$index.'.total_price') is-invalid @enderror">
                                @error('plotSizes.'.$index.'.total_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Booking Amount</label>
                                <input type="number" step="0.01" wire:model="plotSizes.{{ $index }}.booking_amount" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Confirmation Amount</label>
                                <input type="number" step="0.01" wire:model="plotSizes.{{ $index }}.confirmation_amount" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Possession Amount</label>
                                <input type="number" step="0.01" wire:model="plotSizes.{{ $index }}.possession_amount" class="form-control">
                            </div>
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-md-3">
                                <label class="form-label small">Installment Amount</label>
                                <input type="number" step="0.01" wire:model="plotSizes.{{ $index }}.installment_amount" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">No. of Installments</label>
                                <input type="number" wire:model="plotSizes.{{ $index }}.installment_count" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Installment Frequency</label>
                                <select wire:model="plotSizes.{{ $index }}.installment_frequency" class="form-select">
                                    <option value="">—</option>
                                    @foreach ($installmentFrequencies as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Notes (optional)</label>
                                <input type="text" placeholder="Special terms" wire:model="plotSizes.{{ $index }}.notes" class="form-control">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($amenities->isNotEmpty())
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 ssm-form-section-title"><i class="bi bi-stars"></i> Amenities</h2>
                    <div class="row row-cols-2 row-cols-md-3">
                        @foreach ($amenities as $amenity)
                            <div class="col">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="project-amenity-{{ $amenity->id }}"
                                        value="{{ $amenity->id }}" wire:model="selectedAmenities">
                                    <label class="form-check-label" for="project-amenity-{{ $amenity->id }}">{{ $amenity->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-images"></i> Media</h2>

                <div class="mb-3">
                    <label class="form-label">Cover Image (optional)</label>
                    @if ($existingCoverUrl)
                        <div class="mb-2"><img src="{{ $existingCoverUrl }}" class="rounded" width="160" height="107" style="object-fit: cover;"></div>
                    @endif
                    <input type="file" wire:model="coverImage" accept="image/*" class="form-control @error('coverImage') is-invalid @enderror">
                    @error('coverImage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="coverImage" class="form-text">Uploading...</div>
                    @if ($coverImage)
                        <img src="{{ $coverImage->temporaryUrl() }}" class="mt-2 rounded" width="160" height="107" style="object-fit: cover;">
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Gallery Images (optional)</label>
                    <input type="file" wire:model="galleryImages" accept="image/*" multiple class="form-control @error('galleryImages.*') is-invalid @enderror">
                    @error('galleryImages.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="galleryImages" class="form-text">Uploading...</div>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach ($galleryImages as $image)
                            <img src="{{ $image->temporaryUrl() }}" class="rounded" width="100" height="80" style="object-fit: cover;">
                        @endforeach

                        @foreach ($existingGallery as $media)
                            <div class="position-relative">
                                <img src="{{ $media->getUrl() }}" class="rounded" width="100" height="80" style="object-fit: cover;">
                                <button type="button"
                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                    style="width: 20px; height: 20px; line-height: 1;"
                                    wire:click="removeGalleryImage({{ $media->id }})">&times;</button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Brochure (PDF, optional)</label>
                    @if ($existingBrochureUrl)
                        <div class="mb-2"><a href="{{ $existingBrochureUrl }}" target="_blank">View current brochure</a></div>
                    @endif
                    <input type="file" wire:model="brochure" accept="application/pdf" class="form-control @error('brochure') is-invalid @enderror">
                    @error('brochure') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="brochure" class="form-text">Uploading...</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-telephone"></i> Contact</h2>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Phone (optional)</label>
                        <input type="text" wire:model="contactPhone" class="form-control @error('contactPhone') is-invalid @enderror">
                        @error('contactPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Email (optional)</label>
                        <input type="text" wire:model="contactEmail" class="form-control @error('contactEmail') is-invalid @enderror">
                        @error('contactEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Video URL (optional)</label>
                        <input type="text" placeholder="e.g. https://www.youtube.com/watch?v=..."
                            wire:model="videoUrl" class="form-control @error('videoUrl') is-invalid @enderror">
                        @error('videoUrl') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">A YouTube or Vimeo link — shown as an embedded video on the project's detail page.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-eye"></i> Visibility</h2>

                <div class="form-check form-switch">
                    <input type="checkbox" wire:model="isActive" class="form-check-input" id="project-active">
                    <label class="form-check-label" for="project-active">Visible on the website</label>
                </div>
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" wire:model="isFeatured" class="form-check-input" id="project-featured">
                    <label class="form-check-label" for="project-featured">Featured</label>
                </div>
            </div>
        </div>

        @include('admin.partials.seo-fields')

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-2"></span>
                {{ $projectId ? 'Save Changes' : 'Add Project' }}
            </button>
        </div>
    </form>
</div>
