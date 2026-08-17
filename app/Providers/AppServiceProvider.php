<?php

namespace App\Providers;

use App\Models\CdnAsset;
use App\Models\FooterWidget;
use App\Models\HeaderTemplate;
use App\Models\Menu;
use App\Models\PropertyInquiry;
use App\Models\Setting;
use App\Observers\PropertyInquiryObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PropertyInquiry::observe(PropertyInquiryObserver::class);

        Paginator::useBootstrapFive();

        $this->configureDynamicMail();

        View::composer('frontend.partials.header', function ($view) {
            $view->with('headerMenuTree', Menu::cachedTree('header'));
            $view->with('siteName', Setting::get('site_name', config('app.name')));
            $view->with('siteLogoUrl', Setting::getFileUrl('site_logo'));

            // Active header template system
            $activeTemplate = HeaderTemplate::activeTemplate();
            $view->with('activeHeaderTemplate', $activeTemplate);

            if ($activeTemplate) {
                // is-sticky/is-transparent are baked directly into renderHtml()'s <header> tag now
                // (via the {{ $headerClasses }} placeholder in the stored template) — no separate
                // "classes" variable needed here.
                $view->with('headerTemplateHtml', $activeTemplate->renderHtml());
                $view->with('headerTemplateCss', $activeTemplate->renderCss());
                $view->with('headerTemplateJs', $activeTemplate->renderJs());
            }
        });

        // Registered on every frontend document layout (plain pages AND CMS page-template
        // pages) so admin-managed CDN assets actually apply site-wide, not just to one layout.
        View::composer(['frontend.layouts.app', 'frontend.layouts.page-template'], function ($view) {
            $view->with('cdnHeaderAssets', CdnAsset::cachedForLocation('header'));
            $view->with('cdnFooterAssets', CdnAsset::cachedForLocation('footer'));
        });

        View::composer('frontend.partials.footer', function ($view) {
            $view->with('footerColumns', FooterWidget::cachedColumns());
            $view->with('footerSetting', \App\Models\FooterSetting::current());
            $view->with('siteName', Setting::get('site_name', config('app.name')));
        });
    }

    /**
     * Overrides Laravel's .env-driven mail config with admin-managed SMTP settings from the database,
     * per explicit request ("not used from hardcode .env, used from dynamic db"). Deliberately opt-in
     * (gated by the `smtp_enabled` setting, default false) rather than always overriding — an
     * unconfigured/partially-configured DB setting set should never silently break mail that .env was
     * already sending correctly. When disabled (the default, and true for every existing install until
     * an admin explicitly turns this on), .env's MAIL_* variables behave exactly as before — nothing
     * about this method changes that path at all.
     *
     * Runs on every request (this is boot(), not a queued job) — cheap, since Setting::get() is already
     * cache-backed (Cache::rememberForever), so this is at most one cache read per key, no DB query, on
     * every request after the first.
     */
    private function configureDynamicMail(): void
    {
        // Guards against booting before the `settings` table exists — e.g. the moment `php artisan
        // migrate` itself boots the framework on a brand-new install, before this exact migration has
        // run yet. Without this, a fresh `migrate` would crash trying to query a table that doesn't
        // exist yet, purely as a side effect of loading service providers.
        if (! Schema::hasTable('settings')) {
            return;
        }

        if (! (bool) Setting::get('smtp_enabled', false)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => Setting::get('smtp_host'),
            'mail.mailers.smtp.port' => Setting::get('smtp_port'),
            'mail.mailers.smtp.username' => Setting::get('smtp_username'),
            'mail.mailers.smtp.password' => Setting::getEncrypted('smtp_password'),
            'mail.mailers.smtp.encryption' => Setting::get('smtp_encryption') ?: null,
            'mail.from.address' => Setting::get('smtp_from_address') ?: config('mail.from.address'),
            'mail.from.name' => Setting::get('smtp_from_name') ?: config('mail.from.name'),
        ]);
    }
}
