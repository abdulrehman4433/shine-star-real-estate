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
