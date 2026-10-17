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
                <p class="form-text">Each block (e.g. Block A, Block B) is its own entity: it has its own size options (each size with its own dynamic key/value payment rows), and its own media files (uploaded images/videos/PDFs plus video links — shared by all sizes in the block).</p>

                @foreach ($blocks as $index => $block)
                    <div class="border rounded-3 p-3 mb-3">
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

                        {{-- Size options (stored as JSON on this block) --}}
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="small text-muted text-uppercase">Size Options</strong>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addPlotSizeRow({{ $index }})">
                                    <i class="bi bi-plus-lg"></i> Add Size
                                </button>
                            </div>

                            @if (count($block['plot_sizes']))
                                @foreach ($block['plot_sizes'] as $pIndex => $size)
                                    <div class="border rounded p-2 mb-2">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-md-2">
                                                <label class="form-label small">Size</label>
                                                <input type="number" step="0.01" placeholder="e.g. 5"
                                                    wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.size_value"
                                                    class="form-control @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.size_value') is-invalid @enderror">
                                                @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.size_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Unit</label>
                                                <select wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.unit" class="form-select">
                                                    @foreach ($units as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Category (optional)</label>
                                                <input type="text" placeholder="e.g. Residential Plot, Villa, Apartment"
                                                    wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.category" class="form-control">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Bedrooms</label>
                                                <input type="number" wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.bedrooms"
                                                    class="form-control @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.bedrooms') is-invalid @enderror">
                                                @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.bedrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Bathrooms</label>
                                                <input type="number" wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.bathrooms"
                                                    class="form-control @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.bathrooms') is-invalid @enderror">
                                                @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.bathrooms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100" wire:click="removePlotSizeRow({{ $index }}, {{ $pIndex }})" title="Remove size option">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- This size's own payment options (one size option = one payment section) --}}
                                        <div class="mt-2 pt-2 border-top">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small text-muted text-uppercase">Payment Options</span>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="addSizePaymentRow({{ $index }}, {{ $pIndex }})">
                                                    <i class="bi bi-plus-lg"></i> Add Option
                                                </button>
                                            </div>

                                            @if (count($size['payment_options'] ?? []))
                                                @foreach ($size['payment_options'] ?? [] as $oIndex => $option)
                                                    <div class="row g-2 mb-2 align-items-end">
                                                        <div class="col-md-5">
                                                            <label class="form-label small">Key</label>
                                                            <input type="text" placeholder="e.g. Total Payment"
                                                                wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.payment_options.{{ $oIndex }}.key"
                                                                class="form-control form-control-sm @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.payment_options.'.$oIndex.'.key') is-invalid @enderror">
                                                            @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.payment_options.'.$oIndex.'.key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small">Value</label>
                                                            <input type="text" placeholder="e.g. 300k"
                                                                wire:model="blocks.{{ $index }}.plot_sizes.{{ $pIndex }}.payment_options.{{ $oIndex }}.value"
                                                                class="form-control form-control-sm @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.payment_options.'.$oIndex.'.value') is-invalid @enderror">
                                                            @error('blocks.'.$index.'.plot_sizes.'.$pIndex.'.payment_options.'.$oIndex.'.value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-outline-danger w-100" wire:click="removeSizePaymentRow({{ $index }}, {{ $pIndex }}, {{ $oIndex }})" title="Remove option">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="form-text mb-0">No payment options for this size yet — add your own key/value rows (e.g. Total Payment = 300k).</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="form-text mb-1">No size options yet.</div>
                            @endif
                        </div>

                        {{-- This block's own media: images, videos, PDFs --}}
                        <div class="mt-3 pt-3 border-top">
                            @php $mediaRow = $blockMedia->get($block['id'] ?? null) ?? []; @endphp
                            <strong class="small text-muted text-uppercase d-block mb-2">Block Media</strong>

                            <div class="mb-2">
                                <label class="form-label small mb-1">Images</label>
                                <input type="file" wire:model="blockImages.{{ $index }}" accept="image/*" multiple
                                    class="form-control form-control-sm">
                                <div wire:loading wire:target="blockImages.{{ $index }}" class="form-text">Uploading...</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach ($blockImages[$index] ?? [] as $fIndex => $img)
                                        <div class="position-relative">
                                            <img src="{{ $img->temporaryUrl() }}" class="rounded" width="90" height="65" style="object-fit: cover;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                                style="width: 18px; height: 18px; line-height: 1;"
                                                wire:click="removePendingBlockFile({{ $index }}, {{ $fIndex }}, 'images')">&times;</button>
                                        </div>
                                        @error('blockImages.'.$index.'.'.$fIndex) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @endforeach
                                    @foreach ($mediaRow['images'] ?? [] as $media)
                                        <div class="position-relative">
                                            <img src="{{ $media->getUrl() }}" class="rounded" width="90" height="65" style="object-fit: cover;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                                style="width: 18px; height: 18px; line-height: 1;"
                                                wire:click="removeBlockMedia({{ $block['id'] }}, {{ $media->id }})">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small mb-1">Videos</label>
                                <input type="file" wire:model="blockVideos.{{ $index }}" accept="video/*" multiple
                                    class="form-control form-control-sm">
                                <div wire:loading wire:target="blockVideos.{{ $index }}" class="form-text">Uploading...</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach ($blockVideos[$index] ?? [] as $fIndex => $video)
                                        <span class="badge text-bg-light border d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-film"></i> {{ $video->getClientOriginalName() }}
                                            <button type="button" class="btn-close btn-close-sm" style="font-size: 0.6rem;"
                                                wire:click="removePendingBlockFile({{ $index }}, {{ $fIndex }}, 'videos')" aria-label="Remove"></button>
                                        </span>
                                        @error('blockVideos.'.$index.'.'.$fIndex) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @endforeach
                                    @foreach ($mediaRow['videos'] ?? [] as $media)
                                        <div class="position-relative" style="width: 160px;">
                                            <video src="{{ $media->getUrl() }}" controls preload="metadata"
                                                class="rounded w-100" style="height: 95px; object-fit: cover; background: #000;"></video>
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                                style="width: 18px; height: 18px; line-height: 1;"
                                                wire:click="removeBlockMedia({{ $block['id'] }}, {{ $media->id }})">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small mb-1">Video Links (YouTube/Vimeo)</label>
                                @foreach ($blockVideoUrls[$index] ?? [''] as $uIndex => $link)
                                    <div class="input-group input-group-sm mb-1">
                                        <input type="text" placeholder="e.g. https://www.youtube.com/watch?v=..."
                                            wire:model="blockVideoUrls.{{ $index }}.{{ $uIndex }}"
                                            class="form-control @error('blockVideoUrls.'.$index.'.'.$uIndex) is-invalid @enderror">
                                        <button type="button" class="btn btn-outline-danger"
                                            wire:click="removeBlockVideoUrlRow({{ $index }}, {{ $uIndex }})" title="Remove this link">&times;</button>
                                    </div>
                                    @error('blockVideoUrls.'.$index.'.'.$uIndex)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                @endforeach
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addBlockVideoUrlRow({{ $index }})">
                                    <i class="bi bi-plus-lg"></i> Add Video Link
                                </button>
                                <div class="form-text">Uploaded video files and video links both appear in this block's Videos tab on the frontend.</div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label small mb-1">PDFs</label>
                                <input type="file" wire:model="blockPdfs.{{ $index }}" accept="application/pdf" multiple
                                    class="form-control form-control-sm">
                                <div wire:loading wire:target="blockPdfs.{{ $index }}" class="form-text">Uploading...</div>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach ($blockPdfs[$index] ?? [] as $fIndex => $pdf)
                                        <span class="badge text-bg-light border d-inline-flex align-items-center gap-1">
                                            <i class="bi bi-file-earmark-pdf"></i> {{ $pdf->getClientOriginalName() }}
                                            <button type="button" class="btn-close btn-close-sm" style="font-size: 0.6rem;"
                                                wire:click="removePendingBlockFile({{ $index }}, {{ $fIndex }}, 'pdfs')" aria-label="Remove"></button>
                                        </span>
                                        @error('blockPdfs.'.$index.'.'.$fIndex) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @endforeach
                                    @foreach ($mediaRow['pdfs'] ?? [] as $media)
                                        <span class="badge text-bg-light border d-inline-flex align-items-center gap-1">
                                            <a href="{{ $media->getUrl() }}" target="_blank" class="text-decoration-none">
                                                <i class="bi bi-file-earmark-pdf"></i> {{ $media->file_name }}
                                            </a>
                                            <button type="button" class="btn-close btn-close-sm" style="font-size: 0.6rem;"
                                                wire:click="removeBlockMedia({{ $block['id'] }}, {{ $media->id }})" aria-label="Remove"></button>
                                        </span>
                                    @endforeach
                                </div>
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
                <h2 class="h6 ssm-form-section-title"><i class="bi bi-camera-video"></i> Videos</h2>

                <div class="mb-3">
                    <label class="form-label">Video Links (optional)</label>

                    @foreach ($videoLinks as $index => $link)
                        <div class="input-group mb-2">
                            <input type="text" placeholder="e.g. https://www.youtube.com/watch?v=..."
                                wire:model="videoLinks.{{ $index }}"
                                class="form-control @error('videoLinks.'.$index) is-invalid @enderror">
                            <button type="button" class="btn btn-outline-danger"
                                wire:click="removeVideoLinkRow({{ $index }})" title="Remove this link">&times;</button>
                        </div>
                        @error('videoLinks.'.$index)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    @endforeach

                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addVideoLinkRow">
                        <i class="bi bi-plus-lg"></i> Add another video link
                    </button>
                    <div class="form-text mt-1">A YouTube or Vimeo link — embedded on the project's detail page. Use the button above to attach more than one.</div>
                </div>

                <div class="mb-0">
                    <label class="form-label">Upload Videos (optional)</label>
                    <input type="file" wire:model="videoFiles" accept="video/*" multiple
                        class="form-control @error('videoFiles.*') is-invalid @enderror">
                    @error('videoFiles.*') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="videoFiles" class="form-text">Uploading...</div>
                    <div class="form-text">MP4, WebM, OGG, MOV or AVI — up to 50 MB per file (server PHP upload limits apply). Uploaded videos play directly on the detail page.</div>

                    @if ($existingVideos->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($existingVideos as $media)
                                <div class="position-relative" style="width: 180px;">
                                    <video src="{{ $media->getUrl() }}" controls preload="metadata"
                                        class="rounded w-100" style="height: 110px; object-fit: cover; background: #000;"></video>
                                    <button type="button"
                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0"
                                        style="width: 20px; height: 20px; line-height: 1;"
                                        wire:click="removeExistingVideo({{ $media->id }})">&times;</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
