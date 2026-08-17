@extends('frontend.layouts.app')

@section('title', $property->seo_title)

@include('partials.seo-meta', ['seoable' => $property])

@push('styles')
<style>
    .property-detail{
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
    .property-detail h1, .property-detail h2, .property-detail h3, .property-detail h4, .property-detail h5, .property-detail h6{
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
    .pd-gallery-fav{position:absolute;top:16px;right:16px;z-index:6;}
    @media (max-width:767px){
        .pd-gallery, .pd-gallery-col img, .pd-gallery-empty{height:220px;}
    }

    /* Panels */
    .pd-panel{background:var(--pd-card);border-radius:12px;box-shadow:0 2px 14px rgba(15,40,90,.06);margin-bottom:22px;overflow:hidden;}
    .pd-panel-pad{padding:26px 28px;}
    @media (max-width:575px){ .pd-panel-pad{padding:18px; } }

    .pd-badge{background:var(--pd-peach);color:var(--pd-peach-text);border:1px solid var(--pd-peach-border);font-size:.72rem;font-weight:600;padding:5px 14px;border-radius:20px;display:inline-block;}
    .pd-badge + .pd-badge{margin-left:6px;background:var(--pd-mint);color:var(--pd-mint-text);border-color:var(--pd-mint-border);}
    .pd-price{color:var(--pd-blue);font-weight:800;font-size:1.6rem;}
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
    @media (max-width:575px){
        .pd-panel .accordion-button{padding:16px 18px;}
        .pd-panel .accordion-body{padding:0 18px 20px;}
    }

    .pd-feat-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #eef1f6;}
    .pd-feat-row:last-child{border-bottom:none;}
    .pd-feat-row span:first-child{font-weight:600;color:var(--pd-navy);}
    .pd-feat-row span:last-child{color:#8892a4;}

    .pd-amenity{display:flex;align-items:center;gap:10px;margin-bottom:16px;color:#3d4a63;font-weight:500;}
    .pd-amenity i{color:#2ec27e;font-size:1.05rem;}

    .pd-map{width:100%;height:320px;border-radius:10px;}

    /* Sidebar */
    .pd-contact-card .pd-contact-head{background:linear-gradient(120deg,var(--pd-blue),var(--pd-navy));padding:20px 24px;display:flex;align-items:center;gap:14px;color:#fff;}
    .pd-contact-avatar{width:52px;height:52px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;overflow:hidden;}
    .pd-contact-avatar img{width:100%;height:100%;object-fit:cover;}
    .pd-contact-body{padding:22px 24px;}
    .pd-contact-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0f2f7;color:var(--pd-text);text-decoration:none;}
    .pd-contact-row:last-child{border-bottom:none;}
    .pd-contact-row i{color:var(--pd-blue);font-size:1.05rem;}
    .pd-contact-row:hover{color:var(--pd-blue-dark);}

    .pd-calc-card .pd-calc-head{background:linear-gradient(120deg,var(--pd-navy),#123a73);padding:22px 24px;color:#fff;}
    .pd-calc-card .pd-calc-head h5{color:#fff;margin-bottom:4px;}
    .pd-calc-card form{padding:22px 24px;}
    .pd-input-icon{position:relative;}
    .pd-input-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--pd-muted);font-size:.9rem;}
    .pd-input-icon .form-control{padding-left:36px;border-radius:8px;}
    .pd-btn-primary{background:var(--pd-blue);border:none;color:#fff;font-weight:600;border-radius:8px;padding:12px;width:100%;}
    .pd-btn-primary:hover{background:var(--pd-blue-dark);color:#fff;}
    .pd-calc-result{background:#f4f6fb;border-radius:10px;text-align:center;padding:16px;margin-top:14px;}
    .pd-calc-result .num{font-size:1.6rem;font-weight:800;color:var(--pd-navy);}
    .pd-calc-result .lbl{font-size:.8rem;color:var(--pd-muted);}

    .pd-op-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #f0f2f7;}
    .pd-op-item:last-child{border-bottom:none;}
    .pd-op-item img, .pd-op-item .pd-op-noimg{width:64px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;background:#eef1f6;display:flex;align-items:center;justify-content:center;color:var(--pd-muted);font-size:.7rem;}
    .pd-op-item h6{font-size:.87rem;margin-bottom:3px;font-weight:700;color:var(--pd-navy);}
    .pd-op-item h6 a{color:inherit;text-decoration:none;}
    .pd-op-item h6 a:hover{color:var(--pd-blue);}
    .pd-op-item .pd-op-loc{font-size:.76rem;color:var(--pd-muted);}
</style>
@endpush

@section('content')
<div class="property-detail">
    @if (! $property->isApproved())
        <div class="alert alert-warning mb-0 text-center rounded-0">
            This listing is <strong>{{ ucfirst($property->status) }}</strong> and is only visible to you because
            you are the owner or a staff member.
        </div>
    @endif

    @php
        $galleryImages = collect([$property->featured_image_url])
            ->merge($property->getMedia('gallery')->map->getUrl())
            ->filter()
            ->values();
        $isPurchaseType = ! in_array(optional($property->type)->name, ['Rent', 'Lease'], true);
    @endphp

    <div class="pd-gallery" x-data="{ images: @js($galleryImages), index: 0 }">
        <template x-if="images.length === 0">
            <div class="pd-gallery-empty w-100">No Image</div>
        </template>
        <template x-if="images.length > 0">
            <div class="pd-gallery-col">
                <img :src="images[index % images.length]" :alt="@js($property->title)">
            </div>
        </template>
        <template x-if="images.length > 1">
            <div class="pd-gallery-col d-none d-md-block">
                <img :src="images[(index + 1) % images.length]" :alt="@js($property->title)">
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
        <div class="pd-gallery-fav">
            @livewire('frontend.properties.favorite-button', ['property' => $property], key('favorite-'.$property->id))
        </div>
    </div>

    <div class="container-xl py-4">
        <div class="row g-4">

            {{-- ============ MAIN COLUMN ============ --}}
            <div class="col-lg-8">

                <div class="pd-panel pd-panel-pad">
                    @if ($property->type)
                        <span class="pd-badge">{{ $property->type->name }}</span>
                    @endif
                    @if ($property->category)
                        <span class="pd-badge">{{ $property->category->name }}</span>
                    @endif

                    <h1 class="h4 mt-3 mb-1">{{ $property->title }}</h1>
                    <p class="text-muted mb-2">
                        <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                        {{ $property->address }}{{ $property->address && $property->city ? ',' : '' }} {{ $property->city }}
                    </p>

                    <div class="row align-items-center g-3">
                        <div class="col-6">
                            <div class="pd-price">
                                {{ $property->formatted_price }}
                                @if ($property->price_type === 'negotiable')
                                    <span class="fw-normal text-muted" style="font-size: 12px;">(Negotiable)</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="dropdown d-inline-block">
                                <button class="btn pd-btn-soft btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-share-fill me-1"></i>Share
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2">
                                    @include('partials.share-buttons', ['shareUrl' => url()->current(), 'shareTitle' => $property->title])
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex flex-wrap gap-4">
                        @if ($property->bedrooms)
                            <span class="pd-meta"><i class="bi bi-door-closed"></i>{{ $property->bedrooms }} Beds</span>
                        @endif
                        @if ($property->bathrooms)
                            <span class="pd-meta"><i class="bi bi-droplet"></i>{{ $property->bathrooms }} Baths</span>
                        @endif
                        @if ($property->size)
                            <span class="pd-meta"><i class="bi bi-arrows-fullscreen"></i>{{ number_format($property->size) }} sqft</span>
                        @endif
                    </div>
                </div>

                <div class="pd-panel">
                    <div class="accordion" id="propertyAccordion">

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdDetail">
                                    Detail &amp; Features
                                </button>
                            </h2>
                            <div id="pdDetail" class="accordion-collapse collapse show" data-bs-parent="#propertyAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="pd-feat-row"><span>Category</span><span>{{ $property->category->name ?? '—' }}</span></div>
                                            <div class="pd-feat-row"><span>Type</span><span>{{ $property->type->name ?? '—' }}</span></div>
                                            <div class="pd-feat-row"><span>Bedrooms</span><span>{{ $property->bedrooms ?: '—' }}</span></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="pd-feat-row"><span>Bathrooms</span><span>{{ $property->bathrooms ?: '—' }}</span></div>
                                            <div class="pd-feat-row"><span>Area Size</span><span>{{ $property->size ? number_format($property->size).' sqft' : '—' }}</span></div>
                                            <div class="pd-feat-row"><span>Price Type</span><span>{{ ucfirst($property->price_type) }}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($property->description)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdDesc">
                                        Description
                                    </button>
                                </h2>
                                <div id="pdDesc" class="accordion-collapse collapse show" data-bs-parent="#propertyAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-0" style="white-space: pre-line;">{{ $property->description }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($property->amenities->isNotEmpty())
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdAmenities">
                                        Amenities
                                    </button>
                                </h2>
                                <div id="pdAmenities" class="accordion-collapse collapse show" data-bs-parent="#propertyAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            @foreach ($property->amenities as $amenity)
                                                <div class="col-sm-4 col-6">
                                                    <div class="pd-amenity"><i class="bi bi-check-circle-fill"></i>{{ $amenity->name }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($property->features->isNotEmpty())
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdFeatures">
                                        Additional Features
                                    </button>
                                </h2>
                                <div id="pdFeatures" class="accordion-collapse collapse show" data-bs-parent="#propertyAccordion">
                                    <div class="accordion-body">
                                        @foreach ($property->features as $feature)
                                            <div class="pd-feat-row"><span>{{ $feature->name }}</span><span>{{ $feature->value }}</span></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($property->lat && $property->lng)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#pdLocation">
                                        Location
                                    </button>
                                </h2>
                                <div id="pdLocation" class="accordion-collapse collapse show" data-bs-parent="#propertyAccordion">
                                    <div class="accordion-body">
                                        <div
                                            wire:ignore
                                            x-data="{
                                                init() {
                                                    const map = L.map($refs.map, { scrollWheelZoom: false }).setView([{{ $property->lat }}, {{ $property->lng }}], 14);
                                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                                        attribution: '&copy; OpenStreetMap contributors',
                                                        maxZoom: 19,
                                                    }).addTo(map);
                                                    L.marker([{{ $property->lat }}, {{ $property->lng }}]).addTo(map);
                                                }
                                            }"
                                        >
                                            <div x-ref="map" class="pd-map"></div>
                                        </div>
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
                        <div class="pd-contact-avatar">
                            @if ($property->owner->avatar_url ?? null)
                                <img src="{{ $property->owner->avatar_url }}" alt="{{ $property->owner->name }}">
                            @else
                                <i class="bi bi-person-fill"></i>
                            @endif
                        </div>
                        <div>
                            <h5 class="mb-0 text-white">{{ $property->owner->name }}</h5>
                            @if ($property->owner->agency_name)
                                <div class="small text-white-50">{{ $property->owner->agency_name }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="pd-contact-body">
                        @if ($property->owner->phone)
                            <a href="tel:{{ $property->owner->phone }}" class="pd-contact-row">
                                <i class="bi bi-telephone-fill"></i> {{ $property->owner->phone }}
                            </a>
                        @endif

                        @auth
                            @if ($property->user_id !== auth()->id())
                                <form method="POST" action="{{ route('chat.start', $property) }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-chat-dots me-1"></i> Chat with Owner
                                    </button>
                                </form>
                            @endif
                        @else
                            <p class="fw-semibold small mb-2 mt-3">Chat with the owner</p>
                            <div style="height: 360px;">
                                @livewire('frontend.chat.guest-chat-box', ['propertyId' => $property->id], key('guest-chat-'.$property->id))
                            </div>
                        @endauth
                    </div>
                </div>

                <div class="mt-3">
                    @livewire('frontend.properties.inquiry-form', ['property' => $property], key('inquiry-'.$property->id))
                </div>

                @if ($isPurchaseType && $property->price > 0)
                    <div class="pd-panel pd-calc-card mt-3" x-data="{
                        price: {{ (int) $property->price }},
                        down: {{ (int) round($property->price * 0.2) }},
                        years: 15,
                        rate: 12,
                        get monthly() {
                            const principal = Math.max(this.price - this.down, 0);
                            const monthlyRate = (this.rate / 100) / 12;
                            const n = this.years * 12;
                            if (principal <= 0 || n <= 0) return 0;
                            if (monthlyRate === 0) return principal / n;
                            return principal * monthlyRate * Math.pow(1 + monthlyRate, n) / (Math.pow(1 + monthlyRate, n) - 1);
                        }
                    }">
                        <div class="pd-calc-head">
                            <h5>Payment Calculator</h5>
                            <p class="mb-0" style="font-size:.85rem;opacity:.85;">Estimate a monthly installment for this property</p>
                        </div>
                        <form onsubmit="return false;">
                            <div class="mb-3 pd-input-icon">
                                <i class="bi bi-cash-stack"></i>
                                <input type="number" class="form-control" x-model.number="price" min="0" placeholder="Property Price">
                            </div>
                            <div class="mb-3 pd-input-icon">
                                <i class="bi bi-wallet2"></i>
                                <input type="number" class="form-control" x-model.number="down" min="0" placeholder="Down Payment">
                            </div>
                            <div class="mb-3 pd-input-icon">
                                <i class="bi bi-calendar3"></i>
                                <input type="number" class="form-control" x-model.number="years" min="1" max="30" placeholder="Term (Years)">
                            </div>
                            <div class="mb-3 pd-input-icon">
                                <i class="bi bi-percent"></i>
                                <input type="number" class="form-control" x-model.number="rate" min="0" max="30" step="0.1" placeholder="Annual Interest Rate">
                            </div>
                            <div class="pd-calc-result">
                                <div class="num">{{ \App\Models\Setting::currencySymbol() }}<span x-text="Math.round(monthly).toLocaleString()"></span></div>
                                <div class="lbl">Estimated monthly payment</div>
                            </div>
                            <p class="text-muted small mt-2 mb-0">Estimate only — actual financing terms depend on your lender.</p>
                        </form>
                    </div>
                @endif

                @if ($otherProperties->isNotEmpty())
                    <div class="pd-panel pd-panel-pad mt-3">
                        <h5 class="mb-3">More Properties</h5>
                        @foreach ($otherProperties as $other)
                            <div class="pd-op-item">
                                @if ($other->featured_thumb_url)
                                    <img src="{{ $other->featured_thumb_url }}" alt="{{ $other->title }}">
                                @else
                                    <div class="pd-op-noimg">No Image</div>
                                @endif
                                <div>
                                    <h6><a href="{{ route('properties.show', $other) }}">{{ $other->title }}</a></h6>
                                    <div class="pd-op-loc"><i class="bi bi-geo-alt"></i> {{ $other->city ?: $other->address }}</div>
                                    <div class="pd-op-loc">{{ $other->formatted_price }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
