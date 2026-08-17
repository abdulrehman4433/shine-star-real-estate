<div class="card">
    <div class="card-body">
        <h2 class="h6">Send Inquiry</h2>

        @if ($sent)
            <div class="alert alert-success">
                Thanks! Your inquiry has been sent to the listing owner.
            </div>
        @endif

        <form wire:submit="send">
            <div class="mb-2">
                <input type="text" wire:model="name" placeholder="Your name" class="form-control form-control-sm @error('name') is-invalid @enderror">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-2">
                <input type="email" wire:model="email" placeholder="Your email" class="form-control form-control-sm @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-2">
                <input type="text" wire:model="phone" placeholder="Phone (optional)" class="form-control form-control-sm @error('phone') is-invalid @enderror">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-2">
                <textarea wire:model="message" rows="4" placeholder="I'm interested in this property..." class="form-control form-control-sm @error('message') is-invalid @enderror"></textarea>
                @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="send">
                Send Inquiry
            </button>
        </form>
    </div>
</div>
