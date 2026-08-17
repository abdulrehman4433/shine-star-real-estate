<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\Notifies;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithFileUploads;

class GeneralManager extends Component
{
    use WithFileUploads;
    use Notifies;

    public string $siteName = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public string $contactAddress = '';

    public string $contactNotifyEmail = '';

    public bool $contactNotifyEnabled = true;

    public string $currency = 'PKR';

    public string $timezone = 'UTC';

    public bool $smsNotificationsEnabled = false;

    public string $whatsappNumber = '';

    public bool $whatsappEnabled = false;

    public bool $maintenanceMode = false;

    public bool $showFeaturedProperties = true;

    public bool $showReviewsSection = true;

    public bool $showProjectsSection = true;

    public $logo;

    public $favicon;

    // ---- SMTP tab (merged in from the former standalone Admin\Settings\SmtpManager page) ----

    public bool $smtpEnabled = false;

    public string $smtpHost = '';

    public string $smtpPort = '587';

    public string $smtpUsername = '';

    public string $smtpPassword = '';

    public string $smtpEncryption = 'tls';

    public string $smtpFromAddress = '';

    public string $smtpFromName = '';

    public string $testEmailAddress = '';

    // Never pre-fill this with the real decrypted secret — see saveSmtp()'s "blank means keep the
    // current password" handling.
    public bool $hasStoredPassword = false;

    public function mount(): void
    {
        $this->siteName = Setting::get('site_name', config('app.name'));
        $this->contactEmail = Setting::get('contact_email', '');
        $this->contactPhone = Setting::get('contact_phone', '');
        $this->contactAddress = Setting::get('contact_address', '');
        $this->contactNotifyEmail = Setting::get('contact_notify_email', '');
        $this->contactNotifyEnabled = (bool) Setting::get('contact_notify_enabled', true);
        $this->currency = Setting::get('currency', 'PKR');
        $this->timezone = Setting::get('timezone', 'UTC');
        $this->smsNotificationsEnabled = (bool) Setting::get('sms_notifications_enabled', false);
        $this->whatsappNumber = Setting::get('whatsapp_number', '+923206292107');
        $this->whatsappEnabled = (bool) Setting::get('whatsapp_enabled', false);
        $this->maintenanceMode = app()->isDownForMaintenance();
        $this->showFeaturedProperties = (bool) Setting::get('show_featured_properties', true);
        $this->showReviewsSection = (bool) Setting::get('show_reviews_section', true);
        $this->showProjectsSection = (bool) Setting::get('show_projects_section', true);

        $this->smtpEnabled = (bool) Setting::get('smtp_enabled', false);
        $this->smtpHost = (string) Setting::get('smtp_host', '');
        $this->smtpPort = (string) Setting::get('smtp_port', '587');
        $this->smtpUsername = (string) Setting::get('smtp_username', '');
        $this->smtpEncryption = (string) Setting::get('smtp_encryption', 'tls');
        $this->smtpFromAddress = (string) Setting::get('smtp_from_address', '');
        $this->smtpFromName = (string) Setting::get('smtp_from_name', '');
        $this->hasStoredPassword = ! empty(Setting::getEncrypted('smtp_password'));
        $this->testEmailAddress = auth()->user()->email ?? '';
    }

    public function render()
    {
        return view('livewire.admin.settings.general-manager', [
            'currencies' => array_keys(Setting::CURRENCIES),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'logoUrl' => Setting::getFileUrl('site_logo'),
            'faviconUrl' => Setting::getFileUrl('site_favicon'),
        ])->extends('admin.layouts.app')->section('content');
    }

    public function save(): void
    {
        $this->validate([
            'siteName' => 'required|string|max:255',
            'contactEmail' => 'nullable|email|max:255',
            'contactPhone' => 'nullable|string|max:50',
            'contactAddress' => 'nullable|string|max:500',
            'contactNotifyEmail' => 'nullable|email|max:255',
            'currency' => 'required|string|max:10',
            'timezone' => 'required|string|max:64',
            'whatsappNumber' => 'nullable|string|max:30',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:512',
        ]);

        Setting::set('site_name', $this->siteName);
        Setting::set('contact_email', $this->contactEmail);
        Setting::set('contact_phone', $this->contactPhone);
        Setting::set('contact_address', $this->contactAddress);
        Setting::set('contact_notify_email', $this->contactNotifyEmail);
        Setting::set('contact_notify_enabled', $this->contactNotifyEnabled ? '1' : '0');
        Setting::set('currency', $this->currency);
        Setting::set('timezone', $this->timezone);
        Setting::set('sms_notifications_enabled', $this->smsNotificationsEnabled ? '1' : '0');
        Setting::set('whatsapp_number', $this->whatsappNumber);
        Setting::set('whatsapp_enabled', $this->whatsappEnabled ? '1' : '0');
        Setting::set('show_featured_properties', $this->showFeaturedProperties ? '1' : '0');
        Setting::set('show_reviews_section', $this->showReviewsSection ? '1' : '0');
        Setting::set('show_projects_section', $this->showProjectsSection ? '1' : '0');

        if ($this->logo) {
            Setting::setFile('site_logo', $this->logo);
            $this->reset('logo');
        }

        if ($this->favicon) {
            Setting::setFile('site_favicon', $this->favicon);
            $this->reset('favicon');
        }

        $this->notifySuccess('Settings saved.');
    }

    public function saveSmtp(): void
    {
        $this->validate([
            'smtpHost' => 'nullable|string|max:255',
            'smtpPort' => 'nullable|numeric',
            'smtpUsername' => 'nullable|string|max:255',
            'smtpPassword' => 'nullable|string|max:255',
            'smtpEncryption' => 'nullable|in:tls,ssl,',
            'smtpFromAddress' => 'nullable|email|max:255',
            'smtpFromName' => 'nullable|string|max:255',
        ]);

        if ($this->smtpEnabled) {
            $this->validate([
                'smtpHost' => 'required|string|max:255',
                'smtpPort' => 'required|numeric',
                'smtpFromAddress' => 'required|email|max:255',
            ], [], [
                'smtpHost' => 'SMTP host',
                'smtpPort' => 'SMTP port',
                'smtpFromAddress' => 'from address',
            ]);
        }

        Setting::set('smtp_enabled', $this->smtpEnabled ? '1' : '0');
        Setting::set('smtp_host', $this->smtpHost);
        Setting::set('smtp_port', $this->smtpPort);
        Setting::set('smtp_username', $this->smtpUsername);
        Setting::set('smtp_encryption', $this->smtpEncryption);
        Setting::set('smtp_from_address', $this->smtpFromAddress);
        Setting::set('smtp_from_name', $this->smtpFromName);

        // Only touch the stored password if the admin actually typed a new one — leaving the field
        // blank on save means "keep whatever's already there", not "clear it".
        if ($this->smtpPassword !== '') {
            Setting::setEncrypted('smtp_password', $this->smtpPassword);
            $this->hasStoredPassword = true;
        }

        $this->reset('smtpPassword');

        $this->notifySuccess('SMTP settings saved.');
    }

    public function sendTestEmail(): void
    {
        $this->validate(['testEmailAddress' => 'required|email']);

        if (! $this->smtpEnabled) {
            $this->notifyWarning('Enable and save SMTP settings first, then send a test.');

            return;
        }

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').' — your SMTP settings are working correctly.',
                function ($message) {
                    $message->to($this->testEmailAddress)->subject('SMTP Test Email');
                }
            );

            $this->notifySuccess('Test email sent to '.$this->testEmailAddress.' — check the inbox (and spam folder).');
        } catch (\Throwable $e) {
            $this->notifyError('Could not send test email: '.$e->getMessage());
        }
    }

    public function toggleMaintenanceMode(): void
    {
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            $this->maintenanceMode = false;
            $this->notifySuccess('Maintenance mode disabled. The site is live again.');
        } else {
            Artisan::call('down', ['--secret' => 'admin-preview']);
            $this->maintenanceMode = true;
            $this->notifyWarning('Maintenance mode enabled — the site is now down for visitors.');
        }
    }
}
