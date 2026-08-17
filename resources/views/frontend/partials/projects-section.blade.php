@if ($projects->isNotEmpty())
    <!-- ============ FEATURED PROJECTS ============ -->
    <section class="section-pad bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap mb-5">
                <div>
                    <div class="section-title-tag">Our Developments</div>
                    <h2 class="section-title mb-2">Featured Projects</h2>
                    <p class="section-desc mb-0">Explore our handpicked real estate projects, from residential societies to commercial developments.</p>
                </div>
                <div class="scroll-controls mt-3">
                    <button id="rentPrev">&#8592;</button>
                    <button id="rentNext">&#8594;</button>
                </div>
            </div>

            <div class="scroll-row" id="rentRow">
                @foreach ($projects as $project)
                    <div class="property-card-wrap">
                        <div class="property-card">
                            <div class="property-img-wrap">
                                <span class="property-badge">{{ $project->typeEnum()->label() }}</span>
                                @if ($project->cover_thumb_url)
                                    <img src="{{ $project->cover_thumb_url }}" alt="{{ $project->title }}">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted small">No Image</div>
                                @endif
                            </div>
                            <div class="property-body">
                                <h5 class="property-title mt-2">
                                    <a href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>
                                </h5>
                                <div class="property-feats">
                                    <span><i class="bi bi-grid-3x3-gap" style="margin-right: 5px;"></i>{{ $project->blocks_count }} Blocks</span>
                                    <span><i class="bi bi-rulers" style="margin-right: 5px;"></i>{{ $project->plot_sizes_count }} Options</span>
                                </div>
                                <div class="property-loc">
                                    <i class="bi bi-geo-alt"></i>
                                    {{ $project->city ?: $project->society_name }}
                                </div>
                                <a href="{{ route('projects.show', $project) }}" class="property-view-btn">View</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-5">
                <a href="{{ route('projects.index') }}" class="btn-primary-custom">Browse More Projects</a>
            </div>
        </div>
    </section>
@endif
