<?php

namespace App\Livewire\Admin\Settings;

use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\Setting;
use Livewire\Component;

class SeoManager extends Component
{
    use Notifies;

    public string $defaultMetaTitle = '';

    public string $defaultMetaDescription = '';

    public string $robotsTxt = '';

    public string $gaMeasurementId = '';

    public string $gscVerification = '';

    public function mount(): void
    {
        $this->defaultMetaTitle = (string) Setting::get('seo_default_meta_title');
        $this->defaultMetaDescription = (string) Setting::get('seo_default_meta_description');
        $this->robotsTxt = (string) Setting::get('seo_robots_txt');
        $this->gaMeasurementId = (string) Setting::get('seo_ga_measurement_id');
        $this->gscVerification = (string) Setting::get('seo_gsc_verification');
    }

    public function save(): void
    {
        $this->validate([
            'defaultMetaTitle' => 'nullable|string|max:255',
            'defaultMetaDescription' => 'nullable|string|max:255',
            'robotsTxt' => 'nullable|string',
            'gaMeasurementId' => 'nullable|string|max:255',
            'gscVerification' => 'nullable|string|max:255',
        ]);

        Setting::set('seo_default_meta_title', $this->defaultMetaTitle);
        Setting::set('seo_default_meta_description', $this->defaultMetaDescription);
        Setting::set('seo_robots_txt', $this->robotsTxt);
        Setting::set('seo_ga_measurement_id', $this->gaMeasurementId);
        Setting::set('seo_gsc_verification', $this->gscVerification);

        $this->notifySuccess('SEO settings saved.');
    }

    public function regenerateSitemap(): void
    {
        GenerateSitemap::dispatchSync();
        $this->notifySuccess('Sitemap regenerated.');
    }

    public function render()
    {
        return view('livewire.admin.settings.seo-manager')
            ->extends('admin.layouts.app')
            ->section('content');
    }
}
