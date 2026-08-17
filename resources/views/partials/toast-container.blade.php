{{--
    App-wide success/error/warning toast queue. Included once per layout (admin + frontend). Fed from
    any Livewire component via the App\Livewire\Concerns\Notifies trait:

        $this->notifySuccess('Property saved.');
        $this->notifyError('Something went wrong.');
        $this->notifyWarning('This property is about to expire.');

    which just calls $this->dispatch('notify', type: '...', message: '...') — a real browser
    CustomEvent, caught below via Alpine's own `x-on:notify.window` (same convention this app already
    used for the pre-existing `settings-saved` event) and pushed into the shared `toasts` Alpine store
    registered in resources/js/app.js. Auto-dismisses after 5s; the close button dismisses immediately.
--}}
<div x-data
    x-on:notify.window="$store.toasts.push($event.detail.type, $event.detail.message)"
    class="ssm-toast-stack"
    aria-live="polite"
    aria-atomic="true">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="ssm-toast" :class="'ssm-toast--' + toast.type" role="alert">
            <i class="bi ssm-toast__icon"
                :class="{
                    'bi-check-circle-fill': toast.type === 'success',
                    'bi-x-circle-fill': toast.type === 'error',
                    'bi-exclamation-triangle-fill': toast.type === 'warning',
                }"></i>
            <div class="ssm-toast__message" x-text="toast.message"></div>
            <button type="button" class="ssm-toast__close" aria-label="Dismiss" @click="$store.toasts.dismiss(toast.id)">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </template>
</div>
