@if ($reviews->isNotEmpty())
    @php($reviewChunks = $reviews->chunk(3))
    <!-- ============ GOOD REVIEWS BY CUSTOMERS ============ -->
    <section class="section-pad">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-title-tag">Testimonials</div>
                <h2 class="section-title">Good Reviews by Customers</h2>
                <p class="section-desc mx-auto">Hear what our customers have to say about their experience with us.</p>
            </div>

            {{-- Desktop / tablet (md and up): three review cards per slide, unchanged. --}}
            <div class="d-none d-md-block">
                <div id="reviewCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                    <div class="carousel-inner">
                        @foreach ($reviewChunks as $chunkIndex => $chunk)
                            <div class="carousel-item {{ $chunkIndex === 0 ? 'active' : '' }}">
                                <div class="row g-4">
                                    @foreach ($chunk as $review)
                                        <div class="col-md-4">
                                            @include('frontend.partials.review-card', ['review' => $review])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($reviewChunks->count() > 1)
                        <div class="text-center mt-4">
                            <button class="btn btn-outline-dark rounded-circle me-2" type="button" data-bs-target="#reviewCarousel" data-bs-slide="prev" aria-label="Previous reviews">&#8592;</button>
                            <button class="btn btn-outline-dark rounded-circle" type="button" data-bs-target="#reviewCarousel" data-bs-slide="next" aria-label="Next reviews">&#8594;</button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mobile only: one review per slide with explicit prev/next controls and
                 auto-rotation (data-bs-ride/interval). The desktop layout above packs
                 three cards into each slide, which on a phone would stack all three
                 vertically inside a single slide. --}}
            <div class="review-mobile d-md-none">
                @if ($reviews->count() > 1)
                    <div id="reviewCarouselMobile" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
                        <div class="carousel-inner">
                            @foreach ($reviews as $review)
                                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                    @include('frontend.partials.review-card', ['review' => $review])
                                </div>
                            @endforeach
                        </div>

                        <div class="review-mobile-controls">
                            <button type="button" aria-label="Previous review" data-bs-target="#reviewCarouselMobile" data-bs-slide="prev">&#8592;</button>
                            <button type="button" aria-label="Next review" data-bs-target="#reviewCarouselMobile" data-bs-slide="next">&#8594;</button>
                        </div>
                    </div>
                @else
                    @include('frontend.partials.review-card', ['review' => $reviews->first()])
                @endif
            </div>
        </div>
    </section>

    <style>
        /* Mobile-only review carousel — this partial ships its own styles because the
           section's other CSS lives in the Home page's stored `css` column (per-page,
           not a project-wide stylesheet) and these rules belong with the markup. */
        @media (max-width: 767.98px) {
            /* Fixed card height so the carousel doesn't jump as slides rotate — review
               text is already clamped to 6 lines by the page CSS, so 250px absorbs every
               realistic length while short quotes stay pinned to the same box. */
            .review-mobile .review-card {
                min-height: 250px;
            }

            .review-mobile-controls {
                display: flex;
                justify-content: center;
                gap: 14px;
                margin-top: 20px;
            }

            .review-mobile-controls button {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                border: 1px solid #d8dee8;
                background: #fff;
                color: var(--dark, #0b1727);
                font-size: 16px;
                line-height: 1;
                transition: .2s;
            }

            .review-mobile-controls button:active {
                background: var(--primary, #002051);
                border-color: var(--primary, #002051);
                color: #fff;
            }
        }
    </style>
@endif
