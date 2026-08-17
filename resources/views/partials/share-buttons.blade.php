@php
    $encodedUrl = urlencode($shareUrl);
    $encodedTitle = urlencode($shareTitle);
@endphp

<div class="d-flex gap-2 mb-3">
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener"
        class="btn btn-sm btn-outline-secondary" title="Share on Facebook">
        <i class="bi bi-facebook"></i>
    </a>
    <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener"
        class="btn btn-sm btn-outline-secondary" title="Share on X / Twitter">
        <i class="bi bi-twitter-x"></i>
    </a>
    <a href="https://wa.me/?text={{ $encodedTitle }}%20{{ $encodedUrl }}" target="_blank" rel="noopener"
        class="btn btn-sm btn-outline-secondary" title="Share on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encodedUrl }}" target="_blank" rel="noopener"
        class="btn btn-sm btn-outline-secondary" title="Share on LinkedIn">
        <i class="bi bi-linkedin"></i>
    </a>
</div>
