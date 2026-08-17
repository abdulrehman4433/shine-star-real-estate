<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Setting;
use App\Notifications\NewContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Shared by every contact-form entry point in the app (the Page Builder's Livewire ContactFormBlock, and
 * the plain-HTML form on the custom-built /contact-us page) so "who gets notified and when" lives in
 * exactly one place rather than drifting between two copies.
 */
class ContactNotifier
{
    public static function notify(ContactMessage $contactMessage): void
    {
        if (! (bool) Setting::get('contact_notify_enabled', true)) {
            return;
        }

        $recipient = Setting::get('contact_notify_email') ?: Setting::get('contact_email');

        if (! $recipient) {
            return;
        }

        try {
            Notification::route('mail', $recipient)->notify(new NewContactMessage($contactMessage));
        } catch (\Throwable $e) {
            Log::warning('Contact form notification email failed, message was still saved: '.$e->getMessage());
        }
    }
}
