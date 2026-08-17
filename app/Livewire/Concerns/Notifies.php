<?php

namespace App\Livewire\Concerns;

/**
 * Shared success/error/warning toast helper for Livewire components — dispatches a browser-wide
 * `notify` event, caught by resources/views/partials/toast-container.blade.php (included in both
 * admin.layouts.app and frontend.layouts.app). Use this instead of a component-local
 * session()->flash()/x-data alert so every create/update/delete action gives the same feedback.
 */
trait Notifies
{
    protected function notifySuccess(string $message): void
    {
        $this->dispatch('notify', type: 'success', message: $message);
    }

    protected function notifyError(string $message): void
    {
        $this->dispatch('notify', type: 'error', message: $message);
    }

    protected function notifyWarning(string $message): void
    {
        $this->dispatch('notify', type: 'warning', message: $message);
    }

    /**
     * Use these instead of the notify*() methods above when the action redirects afterward
     * (e.g. save() then redirect()->route(...)) — a live dispatch() event never survives a page
     * navigation, so this flashes it to session instead. resources/views/partials/flash-to-toast.blade.php
     * (included in both layouts) re-fires it as the same `notify` event once the destination page loads.
     */
    protected function flashSuccess(string $message): void
    {
        session()->flash('flash_notify', ['type' => 'success', 'message' => $message]);
    }

    protected function flashError(string $message): void
    {
        session()->flash('flash_notify', ['type' => 'error', 'message' => $message]);
    }

    protected function flashWarning(string $message): void
    {
        session()->flash('flash_notify', ['type' => 'warning', 'message' => $message]);
    }
}
