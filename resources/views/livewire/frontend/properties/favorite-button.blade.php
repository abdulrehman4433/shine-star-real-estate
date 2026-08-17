<button
    type="button"
    wire:click="toggle"
    wire:loading.attr="disabled"
    wire:target="toggle"
    class="btn btn-sm {{ $favorited ? 'btn-danger' : 'btn-outline-danger' }}"
    title="{{ $favorited ? 'Remove from favorites' : 'Add to favorites' }}"
>
    {{ $favorited ? '♥ Saved' : '♡ Save' }}
</button>
