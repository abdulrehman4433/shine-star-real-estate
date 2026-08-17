<div class="container" style="max-width: 600px;">
    @if ($sent)
        <div class="alert alert-success">Thanks for reaching out! We'll get back to you soon.</div>
        <button type="button" wire:click="$set('sent', false)" class="btn btn-outline-secondary btn-sm">
            Send another message
        </button>
    @else
        <form wire:submit="send">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <input type="text" wire:model="name" placeholder="Your name" class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <input type="email" wire:model="email" placeholder="Your email" class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <input type="text" wire:model="phone" placeholder="Phone (optional)" class="form-control @error('phone') is-invalid @enderror">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <input type="text" wire:model="subject" placeholder="Subject (optional)" class="form-control @error('subject') is-invalid @enderror">
                    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <textarea wire:model="message" rows="4" placeholder="Your message" class="form-control @error('message') is-invalid @enderror"></textarea>
                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="send">
                <span wire:loading.remove wire:target="send">Send Message</span>
                <span wire:loading wire:target="send">Sending&hellip;</span>
            </button>
        </form>
    @endif
</div>
