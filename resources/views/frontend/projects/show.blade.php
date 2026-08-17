@extends('frontend.layouts.app')

@section('title', $project->seo_title)

@include('partials.seo-meta', ['seoable' => $project])

@push('styles')
<style>
    .project-detail{
        --pd-navy:#0b2545;
        --pd-blue:#1e63e0;
        --pd-blue-dark:#154bb0;
        --pd-mint:#eafaf3;
        --pd-mint-border:#bfe9d6;
        --pd-mint-text:#12866f;
        --pd-peach:#fdf1ea;
        --pd-peach-border:#f3d3bb;
        --pd-peach-text:#e07a3f;
        --pd-bg:#eef3fb;
        --pd-card:#ffffff;
        --pd-muted:#7c8aa0;
        --pd-text:#1b2a41;
        background:var(--pd-bg);
        color:var(--pd-text);
        padding-bottom: 3rem;
    }
    .project-detail h1, .project-detail h2, .project-detail h3, .project-detail h4, .project-detail h5, .project-detail h6{
        font-weight:700;color:var(--pd-navy);
    }

    /* Gallery */
    .pd-gallery{position:relative;display:flex;gap:6px;overflow:hidden;height:340px;background:#dfe6f2;}
    .pd-gallery-col{flex:1 1 50%;overflow:hidden;position:relative;}
    .pd-gallery-col img{width:100%;height:340px;object-fit:cover;display:block;}
    .pd-gallery-empty{width:100%;height:340px;display:flex;align-items:center;justify-content:center;color:var(--pd-muted);}
    .pd-gallery-nav{
        position:absolute;top:50%;transform:translateY(-50%);
        width:42px;height:42px;border-radius:50%;background:#fff;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 4px 12px rgba(0,0,0,.15);cursor:pointer;color:var(--pd-navy);z-index:5;border:none;
    }
    .pd-gallery-nav.left{left:16px;}
    .pd-gallery-nav.right{right:16px;}
    @media (max-width:767px){
        .pd-gallery, .pd-gallery-col img, .pd-gallery-empty{height:220px;}
    }

    /* Panels */
    .pd-panel{background:var(--pd-card);border-radius:12px;box-shadow:0 2px 14px rgba(15,40,90,.06);margin-bottom:22px;overflow:hidden;}
    .pd-panel-pad{padding:26px 28px;}
    @media (max-width:575px){ .pd-panel-pad{padding:18px; } }

    .pd-badge{background:var(--pd-peach);color:var(--pd-peach-text);border:1px solid var(--pd-peach-border);font-size:.72rem;font-weight:600;padding:5px 14px;border-radius:20px;display:inline-block;}
    .pd-badge + .pd-badge{margin-left:6px;background:var(--pd-mint);color:var(--pd-mint-text);border-color:var(--pd-mint-border);}
    .pd-price{color:var(--pd-blue);font-weight:800;font-size:1.5rem;}
    .pd-price-alt{color:var(--pd-navy);font-weight:700;font-size:1.1rem;}
    .pd-meta{color:var(--pd-muted);font-size:.95rem;}
    .pd-meta i{color:var(--pd-blue);margin-right:6px;}

    .pd-btn-soft{background:var(--pd-mint);color:var(--pd-mint-text);border:1px solid var(--pd-mint-border);font-weight:600;border-radius:8px;}
    .pd-btn-soft:hover{background:#dcf3ea;color:var(--pd-mint-text);}

    /* Accordion */
    .pd-panel .accordion-item{border:none;border-bottom:1px solid #eef1f6;}
    .pd-panel .accordion-item:last-child{border-bottom:none;}
    .pd-panel .accordion-button{font-weight:700;color:var(--pd-navy);font-size:1.02rem;padding:18px 28px;background:#fff;box-shadow:none !important;}
    .pd-panel .accordion-button:not(.collapsed){color:var(--pd-navy);background:#fff;}
    .pd-panel .accordion-button::after{background-image:none;font-family:"bootstrap-icons";content:"\f282";font-size:1rem;transition:transform .2s;}
    .pd-panel .accordion-button:not(.collapsed)::after{transform:rotate(180deg);}
    .pd-panel .accordion-body{padding:0 28px 26px;color:#556077;line-height:1.85;}
    .pd-panel .pd-static-header{font-weight:700;color:var(--pd-navy);font-size:1.02rem;padding:18px 28px;margin:0;}
    @media (max-width:575px){
        .pd-panel .accordion-button{padding:16px 18px;}
        .pd-panel .accordion-body{padding:0 18px 20px;}
        .pd-panel .pd-static-header{padding:16px 18px;}
    }

    .pd-panel-head{padding:18px 28px;border-bottom:1px solid #eef1f6;}
    .pd-panel-head h2{font-weight:700;color:var(--pd-navy);font-size:1.02rem;}
    .pd-panel-body{padding:22px 28px;}
    @media (max-width:575px){
        .pd-panel-head{padding:16px 18px;}
        .pd-panel-body{padding:18px;}
    }

    .pd-breadcrumb .breadcrumb-item a{color:var(--pd-blue);text-decoration:none;font-weight:500;}
    .pd-breadcrumb .breadcrumb-item a:hover{color:var(--pd-blue-dark);text-decoration:underline;}
    .pd-breadcrumb .breadcrumb-item.active{color:var(--pd-navy);font-weight:600;}
    .pd-breadcrumb .breadcrumb-item + .breadcrumb-item::before{color:var(--pd-muted);}

    .pd-feat-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #eef1f6;}
    .pd-feat-row:last-child{border-bottom:none;}
    .pd-feat-row span:first-child{font-weight:600;color:var(--pd-navy);font-size:.92rem;}
    .pd-feat-row span:last-child{color:#8892a4;}

    .pd-amenity{display:flex;align-items:center;gap:10px;margin-bottom:16px;color:#3d4a63;font-weight:500;}
    .pd-amenity i{color:#2ec27e;font-size:1.05rem;}

    .pd-plan-table th, .pd-plan-table td{white-space:nowrap;}
    .pd-block-card{width:100%;border:1px solid #eef1f6;border-radius:10px;overflow:hidden;margin-bottom:20px;box-shadow:0 2px 10px rgba(15,40,90,.04);}
    .pd-block-card:last-child{margin-bottom:0;}
    .pd-block-card-head{padding:14px 20px;background:#f8fafc;border-bottom:1px solid #eef1f6;}
    .pd-map{width:100%;height:320px;border-radius:10px;}

    /* Sidebar */
    .pd-contact-card .pd-contact-head{background:linear-gradient(120deg,var(--pd-blue),var(--pd-navy));padding:20px 24px;display:flex;align-items:center;gap:14px;color:#fff;}
    .pd-contact-avatar{width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
    .pd-contact-body{padding:22px 24px;}
    .pd-contact-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0f2f7;color:var(--pd-text);text-decoration:none;}
    .pd-contact-row:last-child{border-bottom:none;}
    .pd-contact-row i{color:var(--pd-blue);font-size:1.05rem;}
    .pd-contact-row:hover{color:var(--pd-blue-dark);}
    .pd-btn-primary{background:var(--pd-blue);border:none;color:#fff;font-weight:600;border-radius:8px;padding:12px;width:100%;display:inline-block;text-align:center;}
    .pd-btn-primary:hover{background:var(--pd-blue-dark);color:#fff;}

    .pd-op-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #f0f2f7;}
    .pd-op-item:last-child{border-bottom:none;}
    .pd-op-item img, .pd-op-item .pd-op-noimg{width:64px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;background:#eef1f6;display:flex;align-items:center;justify-content:center;color:var(--pd-muted);font-size:.7rem;}
    .pd-op-item h6{font-size:.87rem;margin-bottom:3px;font-weight:700;color:var(--pd-navy);}
    .pd-op-item h6 a{color:inherit;text-decoration:none;}
    .pd-op-item h6 a:hover{color:var(--pd-blue);}
    .pd-op-item .pd-op-loc{font-size:.76rem;color:var(--pd-muted);}

    /* CTA banner */
    .pd-cta{background:linear-gradient(120deg,var(--pd-blue),var(--pd-navy));padding:40px 0;color:#fff;border-radius:12px;}
    .pd-cta h4{color:#fff;margin-bottom:4px;}
    .pd-cta p{color:rgba(255,255,255,.85);margin-bottom:0;}
    .pd-btn-cta{background:#fff;color:var(--pd-navy);font-weight:700;border-radius:30px;padding:12px 30px;border:none;text-decoration:none;display:inline-block;}
    .pd-btn-cta:hover{background:#f0f3f9;color:var(--pd-navy);}
</style>
@endpush

@section('content')
<div class="project-detail">
    @if (! $project->is_active)
        <div class="alert alert-warning mb-0 text-center rounded-0">
            This project is <strong>hidden</strong> and is only visible to you because you're a staff member.
        </div>
    @endif

    @php
        $galleryImages = collect([$project->cover_url])
            ->merge($project->getMedia('gallery')->map->getUrl())
            ->filter()
            ->values();
        $currencySymbol = \App\Models\Setting::currencySymbol();
    @endphp

    <div class="pd-gallery" x-data="{ images: @js($galleryImages), index: 0 }">
        <template x-if="images.length === 0">
            <div class="pd-gallery-empty w-100">No Image</div>
        </template>
        <template x-if="images.length > 0">
            <div class="pd-gallery-col">
                <img :src="images[index % images.length]" :alt="@js($project->title)">
            </div>
        </template>
        <template x-if="images.length > 1">
            <div class="pd-gallery-col d-none d-md-block">
                <img :src="images[(index + 1) % images.length]" :alt="@js($project->title)">
            </div>
        </template>
        <template x-if="images.length > 2">
            <button type="button" class="pd-gallery-nav left" @click="index = (index - 1 + images.length) % images.length" aria-label="Previous photo">
                <i class="bi bi-arrow-left"></i>
            </button>
        </template>
        <template x-if="images.length > 2">
            <button type="button" class="pd-gallery-nav right" @click="index = (index + 1) % images.length" aria-label="Next photo">
                <i class="bi bi-arrow-right"></i>
            </button>
        </template>
    </div>

    <div class="container-xl py-4">
        <nav class="pd-breadcrumb mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $project->title }}</li>
            </ol>
        </nav>

        <div class="row g-4">

            {{-- ============ MAIN COLUMN ============ --}}
            <div class="col-lg-8">

                <div class="pd-panel pd-panel-pad">
                    <span class="pd-badge">{{ $project->typeEnum()->label() }}</span>
                    @if ($project->is_featured)
                        <span class="pd-badge">Featured</span>
                    @endif

                    <h1 class="h4 mt-3 mb-1">{{ $project->title }}</h1>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                        {{ $project->address }}{{ $project->address && $project->city ? ',' : '' }} {{ $project->city }}
                    </p>

                    <div class="d-flex justify-content-end">
                        <div class="dropdown">
                            <button class="btn pd-btn-soft btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-share-fill me-1"></i>Share
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2">
                                @include('partials.share-buttons', ['shareUrl' => url()->current(), 'shareTitle' => $project->title])
                            </div>
                        </div>
                    </div>

                </div>

                <div class="pd-panel">
                    <div class="accordion" id="projectAccordion">

                        <div class="accordion-item">
                            <h2 class="pd-static-header">Detail &amp; Features</h2>
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="pd-feat-row"><span>Society / Development</span><span>{{ $project->society_name ?: '—' }}</span></div>
                                        <div class="pd-feat-row"><span>Developer</span><span>{{ $project->developer_name ?: '—' }}</span></div>
                                        <div class="pd-feat-row"><span>Type</span><span>{{ $project->typeEnum()->label() }}</span></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="pd-feat-row"><span>Blocks</span><span>{{ $project->blocks->count() }}</span></div>
                                        <div class="pd-feat-row"><span>Size Options</span><span>{{ $project->plotSizes->count() }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($project->description)
                            <div class="accordion-item">
                                <h2 class="pd-static-header">Description</h2>
                                <div class="accordion-body">
                                    <p class="mb-0" style="white-space: pre-line;">{{ $project->description }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($project->amenities->isNotEmpty())
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdAmenities">
                                        Amenities
                                    </button>
                                </h2>
                                <div id="pdAmenities" class="accordion-collapse collapse show" data-bs-parent="#projectAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            @foreach ($project->amenities as $amenity)
                                                <div class="col-sm-4 col-6">
                                                    <div class="pd-amenity"><i class="bi bi-check-circle-fill"></i>{{ $amenity->name }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($project->brochure_url)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdBrochure">
                                        Brochure
                                    </button>
                                </h2>
                                <div id="pdBrochure" class="accordion-collapse collapse" data-bs-parent="#projectAccordion">
                                    <div class="accordion-body">
                                        <a href="{{ $project->brochure_url }}" target="_blank" rel="noopener" class="btn pd-btn-soft px-4">
                                            <i class="bi bi-file-earmark-pdf"></i> Download Brochure
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($project->video_embed_url)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdVideo">
                                        Video
                                    </button>
                                </h2>
                                <div id="pdVideo" class="accordion-collapse collapse" data-bs-parent="#projectAccordion">
                                    <div class="accordion-body">
                                        <div class="ratio ratio-16x9">
                                            <iframe src="{{ $project->video_embed_url }}" title="{{ $project->title }} video" allowfullscreen loading="lazy"></iframe>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($project->lat && $project->lng)
                            <div class="accordion-item">
                                <h2 class="pd-static-header">Location</h2>
                                <div class="accordion-body">
                                    <div
                                        wire:ignore
                                        x-data="{
                                            init() {
                                                const map = L.map($refs.map, { scrollWheelZoom: false }).setView([{{ $project->lat }}, {{ $project->lng }}], 14);
                                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                    attribution: '&copy; OpenStreetMap contributors',
                                                    maxZoom: 19,
                                                }).addTo(map);
                                                L.marker([{{ $project->lat }}, {{ $project->lng }}]).addTo(map);
                                            }
                                        }"
                                    >
                                        <div x-ref="map" class="pd-map"></div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>

            {{-- ============ SIDEBAR ============ --}}
            <div class="col-lg-4">

                <div class="pd-panel pd-contact-card">
                    <div class="pd-contact-head">
                        <div class="pd-contact-avatar"><i class="bi bi-building"></i></div>
                        <h5 class="mb-0 text-white">{{ $project->developer_name ?: $project->society_name ?: 'Project Contact' }}</h5>
                    </div>
                    <div class="pd-contact-body">
                        @if ($project->contact_phone)
                            <a href="tel:{{ $project->contact_phone }}" class="pd-contact-row">
                                <i class="bi bi-telephone-fill"></i> {{ $project->contact_phone }}
                            </a>
                        @endif
                        @if ($project->contact_email)
                            <a href="mailto:{{ $project->contact_email }}" class="pd-contact-row">
                                <i class="bi bi-envelope-fill"></i> {{ $project->contact_email }}
                            </a>
                        @endif
                        @if (! $project->contact_phone && ! $project->contact_email)
                            <p class="text-muted small mb-0">No contact details have been added for this project yet.</p>
                        @endif

                        @if ($project->contact_phone)
                            <a href="tel:{{ $project->contact_phone }}" class="pd-btn-primary mt-3">
                                <i class="bi bi-telephone-fill me-1"></i> Call Now
                            </a>
                        @endif
                    </div>
                </div>

                @if ($otherProjects->isNotEmpty())
                    <div class="pd-panel pd-panel-pad">
                        <h5 class="mb-3">More Projects</h5>
                        @foreach ($otherProjects as $other)
                            <div class="pd-op-item">
                                @if ($other->cover_thumb_url)
                                    <img src="{{ $other->cover_thumb_url }}" alt="{{ $other->title }}">
                                @else
                                    <div class="pd-op-noimg">No Image</div>
                                @endif
                                <div>
                                    <h6><a href="{{ route('projects.show', $other) }}">{{ $other->title }}</a></h6>
                                    <div class="pd-op-loc"><i class="bi bi-geo-alt"></i> {{ $other->city ?: $other->society_name }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>

        @if ($project->blocks->isNotEmpty() || $project->plotSizes->isNotEmpty())
            <div class="pd-panel">
                <div class="pd-panel-head">
                    <h2 class="h6 mb-0">Payment Plans</h2>
                </div>
                <div class="pd-panel-body">
                    @foreach ($project->blocks as $block)
                        @if ($block->plotSizes->isNotEmpty())
                            <div class="pd-block-card">
                                <div class="pd-block-card-head">
                                    <h3 class="h6 mb-0">{{ $block->name }}</h3>
                                    @if ($block->description)
                                        <p class="text-muted small mb-0 mt-1">{{ $block->description }}</p>
                                    @endif
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm pd-plan-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Size</th>
                                                <th>Category</th>
                                                <th>Booking</th>
                                                <th>Installments</th>
                                                <th>Possession</th>
                                                <th>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($block->plotSizes as $plotSize)
                                                <tr>
                                                    <td>{{ $plotSize->label }}</td>
                                                    <td>{{ $plotSize->category ?: '—' }}</td>
                                                    <td>{{ $plotSize->booking_amount ? $currencySymbol.number_format((float) $plotSize->booking_amount) : '—' }}</td>
                                                    <td>
                                                        @if ($plotSize->installment_amount)
                                                            {{ $currencySymbol.number_format((float) $plotSize->installment_amount) }} &times; {{ $plotSize->installment_count }}
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ $plotSize->possession_amount ? $currencySymbol.number_format((float) $plotSize->possession_amount) : '—' }}</td>
                                                    <td>{{ $plotSize->total_price ? $currencySymbol.number_format((float) $plotSize->total_price) : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if ($unassignedPlotSizes->isNotEmpty())
                        <div class="pd-block-card">
                            <div class="pd-block-card-head">
                                <h3 class="h6 mb-0">No Specific Block</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm pd-plan-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Size</th>
                                            <th>Category</th>
                                            <th>Booking</th>
                                            <th>Installments</th>
                                            <th>Possession</th>
                                            <th>Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unassignedPlotSizes as $plotSize)
                                            <tr>
                                                <td>{{ $plotSize->label }}</td>
                                                <td>{{ $plotSize->category ?: '—' }}</td>
                                                <td>{{ $plotSize->booking_amount ? $currencySymbol.number_format((float) $plotSize->booking_amount) : '—' }}</td>
                                                <td>
                                                    @if ($plotSize->installment_amount)
                                                        {{ $currencySymbol.number_format((float) $plotSize->installment_amount) }} &times; {{ $plotSize->installment_count }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $plotSize->possession_amount ? $currencySymbol.number_format((float) $plotSize->possession_amount) : '—' }}</td>
                                                <td>{{ $plotSize->total_price ? $currencySymbol.number_format((float) $plotSize->total_price) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="pd-cta mt-3">
            <div class="container-xl d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 text-center text-md-start">
                <div>
                    <h4>Interested in This Project?</h4>
                    <p>Get in touch with our team to learn more or schedule a visit.</p>
                </div>
                @if ($project->contact_phone)
                    <a href="tel:{{ $project->contact_phone }}" class="pd-btn-cta">Call Now</a>
                @else
                    <a href="{{ route('projects.index') }}" class="pd-btn-cta">Browse More Projects</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
