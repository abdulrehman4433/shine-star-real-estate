{{--
    Bridges a session-flashed notification into the shared toast system for actions that redirect
    after saving (e.g. Admin\Blog\Posts\Form::save() redirects to the index page — a live
    $this->dispatch('notify', ...) call would never survive that navigation). Set via
    App\Livewire\Concerns\Notifies::flashSuccess()/flashError()/flashWarning() before a redirect();
    this fires the same `notify` window event the toast container listens for, once, on page load.
--}}
@if (session()->has('flash_notify'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notify', { detail: @json(session('flash_notify')) }));
        });
    </script>
@endif
