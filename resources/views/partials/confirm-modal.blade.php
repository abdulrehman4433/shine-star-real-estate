{{--
    App-wide replacement for the browser's native confirm() / Livewire's wire:confirm (which is just a
    thin wrapper around the same native dialog). Included once per layout (admin + frontend) — every
    button anywhere on the page can trigger it via the shared Alpine store registered in resources/js/app.js:

        <button type="button"
            @click="$store.confirm.open({ message: 'Delete this property?' }).then(ok => ok && $wire.delete(5))">
            Delete
        </button>

    `open()` returns a Promise that resolves true/false depending on which button the user clicked —
    nothing runs until that resolves, so the pattern is identical in shape to `if (confirm(...)) { ... }`.
--}}
<div x-data
    x-show="$store.confirm.show"
    x-cloak
    class="modal ssm-confirm-modal"
    :class="{ 'd-block': $store.confirm.show }"
    style="background: rgba(5, 45, 100, .45);"
    tabindex="-1"
    @keydown.escape.window="$store.confirm.show && $store.confirm.cancel()">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ssm-confirm-modal__content">
            <div class="modal-body text-center pt-4 pb-2">
                <div class="ssm-confirm-modal__icon" :class="'ssm-confirm-modal__icon--' + $store.confirm.variant">
                    <i class="bi" :class="$store.confirm.variant === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'"></i>
                </div>
                <h5 class="mt-3 mb-2" x-text="$store.confirm.title"></h5>
                <p class="text-muted mb-0" x-text="$store.confirm.message"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4 pt-2">
                <button type="button" class="btn btn-outline-secondary px-4" @click="$store.confirm.cancel()">Cancel</button>
                <button type="button" class="btn px-4" :class="'btn-' + $store.confirm.variant"
                    @click="$store.confirm.confirm()" x-text="$store.confirm.confirmText"></button>
            </div>
        </div>
    </div>
</div>
