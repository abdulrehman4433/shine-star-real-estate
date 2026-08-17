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

            <div id="reviewCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($reviewChunks as $chunkIndex => $chunk)
                        <div class="carousel-item {{ $chunkIndex === 0 ? 'active' : '' }}">
                            <div class="row g-4">
                                @foreach ($chunk as $review)
                                    <div class="col-md-4">
                                        <div class="review-card">
                                            <p>{{ $review->content }}</p>
                                            <div class="review-user">
                                                @if ($review->photo_thumb_url)
                                                    <img src="{{ $review->photo_thumb_url }}" alt="{{ $review->customer_name }}">
                                                @else
                                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 50 50'%3E%3Ccircle cx='25' cy='25' r='25' fill='%23e5e7eb'/%3E%3Ccircle cx='25' cy='20' r='9' fill='%239ca3af'/%3E%3Cpath d='M8 46c2-10 10-16 17-16s15 6 17 16' fill='%239ca3af'/%3E%3C/svg%3E" alt="{{ $review->customer_name }}">
                                                @endif
                                                <div>
                                                    <h6>{{ $review->customer_name }}</h6>
                                                    @if ($review->customer_role)
                                                        <span>{{ $review->customer_role }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($reviewChunks->count() > 1)
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-dark rounded-circle me-2" type="button" data-bs-target="#reviewCarousel" data-bs-slide="prev">&#8592;</button>
                        <button class="btn btn-outline-dark rounded-circle" type="button" data-bs-target="#reviewCarousel" data-bs-slide="next">&#8594;</button>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
