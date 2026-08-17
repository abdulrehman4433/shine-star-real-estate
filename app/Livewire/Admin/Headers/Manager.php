<?php

namespace App\Livewire\Admin\Headers;

use App\Livewire\Concerns\Notifies;
use App\Models\HeaderTemplate;
use App\Models\Menu;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;

class Manager extends Component
{
    use WithFileUploads;
    use Notifies;

    public ?int $editingId = null;

    public string $name = '';

    public string $status = 'draft';

    public string $html = '';

    public string $css = '';

    public string $js = '';

    // Settings
    public bool $sticky = true;

    public bool $transparent = false;

    public int $headerHeight = 80;

    public string $headerBgColor = '#ffffff';

    public string $headerTextColor = '#1f2937';

    public string $headerBorderColor = '#e5e7eb';

    public string $dropdownHoverStyle = 'fade';

    public string $menuPosition = 'right';

    public bool $showCta = true;

    public string $ctaLoginLabel = 'Login';

    public string $ctaRegisterLabel = 'Register';

    public string $ctaContactLabel = 'Contact Us';

    public string $ctaContactUrl = '/contact';

    public bool $showSearch = false;

    public string $fontFamily = "'Inter', system-ui, -apple-system, sans-serif";

    public int $navFontSize = 14;

    public int $navFontWeight = 500;

    public int $navItemPaddingX = 16;

    public int $navItemPaddingY = 8;

    public int $logoHeight = 36;

    public int $logoWidth = 140;

    public int $borderRadius = 8;

    public bool $shadowEnabled = true;

    public string $shadowColor = 'rgba(0,0,0,0.08)';

    public int $mobileBreakpoint = 991;

    // UI state
    public string $activeEditorTab = 'html';

    public $logoUpload = null;

    public function mount(): void
    {
        // Auto-load the first header template, or create one if none exists
        $template = HeaderTemplate::orderBy('id')->first();

        if (! $template) {
            $template = HeaderTemplate::create([
                'name' => 'Main Header',
                'status' => 'draft',
                'is_active' => true,
                'html' => HeaderTemplate::defaultHtmlTemplate(),
                'css' => HeaderTemplate::defaultCssTemplate(),
                'js' => HeaderTemplate::defaultJsTemplate(),
                'settings' => HeaderTemplate::defaultSettings(),
            ]);

            HeaderTemplate::forgetActiveCache();
        }

        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->status = $template->status;
        $this->html = $template->html ?? HeaderTemplate::defaultHtmlTemplate();
        $this->css = $template->css ?? HeaderTemplate::defaultCssTemplate();
        $this->js = $template->js ?? '';

        $settings = $template->getSettingsWithDefaults();
        $this->loadSettings($settings);
    }

    public function saveDraft(): void
    {
        $this->status = 'draft';
        $this->save();
    }

    public function publish(): void
    {
        $this->status = 'published';
        $this->save();
    }

    public function save(): bool
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'html' => 'nullable|string',
            'css' => 'nullable|string',
            'js' => 'nullable|string',
        ]);

        if ($this->status === 'published' && empty(trim(strip_tags($this->html)))) {
            $this->addError('html', 'Cannot publish a header with empty HTML content.');
            return false;
        }

        if (! $this->validateMarkup($this->html, $this->css, $this->js, 'html')) {
            return false;
        }

        $template = HeaderTemplate::findOrFail($this->editingId);

        $settings = $this->collectSettings();

        $template->fill([
            'name' => $this->name,
            'status' => $this->status,
            'is_active' => $this->status === 'published',
            'html' => $this->html,
            'css' => $this->css,
            'js' => $this->js,
            'settings' => $settings,
        ])->save();

        // Handle logo upload
        if ($this->logoUpload) {
            $template->addMedia($this->logoUpload->getRealPath())
                ->usingFileName($this->logoUpload->getClientOriginalName())
                ->toMediaCollection('header_logo');
            $this->logoUpload = null;
        }

        HeaderTemplate::forgetActiveCache();

        $this->notifySuccess(
            $this->status === 'published'
                ? 'Header template published.'
                : 'Header template saved.'
        );

        return true;
    }

    private function validateMarkup(string $html, string $css, string $js, string $errorField): bool
    {
        if ($html !== '') {
            libxml_use_internal_errors(true);
            libxml_clear_errors();
            $dom = new \DOMDocument();
            $dom->loadHTML(
                '<!DOCTYPE html><html><body>'.$html.'</body></html>',
                LIBXML_NOERROR | LIBXML_NOWARNING
            );
            $fatalErrors = array_filter(libxml_get_errors(), fn ($e) => $e->level === LIBXML_ERR_FATAL);
            libxml_clear_errors();

            if ($fatalErrors) {
                $first = reset($fatalErrors);
                $this->addError($errorField, 'Header HTML is not well-formed: '.trim($first->message));
                return false;
            }
        }

        if ($css !== '' && substr_count($css, '{') !== substr_count($css, '}')) {
            $this->addError('css', 'Header CSS has unbalanced { } braces.');
            return false;
        }

        if ($js !== '') {
            foreach (['{' => '}', '(' => ')', '[' => ']'] as $open => $close) {
                if (substr_count($js, $open) !== substr_count($js, $close)) {
                    $this->addError('js', "Header JS has unbalanced {$open} {$close} pairs.");
                    return false;
                }
            }
        }

        return true;
    }

    public function removeLogo(): void
    {
        $template = HeaderTemplate::find($this->editingId);
        if ($template) {
            $template->clearMediaCollection('header_logo');
            HeaderTemplate::forgetActiveCache();
        }
    }

    public function switchEditorTab(string $tab): void
    {
        $this->activeEditorTab = $tab;
    }

    public function previewTemplate(): void
    {
        $document = $this->livePreviewDocument();
        $this->dispatch('open-preview', document: $document);
    }

    public function livePreviewHtml(): string
    {
        if (! $this->html) {
            return '';
        }

        $logoUrl = null;
        if ($this->logoUpload) {
            $logoUrl = $this->logoUpload->temporaryUrl();
        } elseif ($this->editingId) {
            $logoUrl = HeaderTemplate::find($this->editingId)?->logoUrl();
        }
        $logoUrl = $logoUrl ?: Setting::getFileUrl('site_logo');

        $siteName = Setting::get('site_name', config('app.name'));
        $menuTree = Menu::cachedTree('header');

        $data = [
            'siteName' => $siteName,
            'homeUrl' => url('/'),
            'headerClasses' => trim(($this->sticky ? 'is-sticky' : '').' '.($this->transparent ? 'is-transparent' : '')),
            'logoHtml' => view('partials.header-template.logo', compact('logoUrl', 'siteName'))->render(),
            'menuHtml' => view('partials.header-template.menu', compact('menuTree'))->render(),
            'searchHtml' => $this->showSearch ? view('partials.header-template.search')->render() : '',
            'ctaHtml' => $this->showCta
                ? view('partials.header-template.cta', [
                    'ctaLoginLabel' => $this->ctaLoginLabel,
                    'ctaRegisterLabel' => $this->ctaRegisterLabel,
                ])->render()
                : '',
            // Resido-style custom template data (inline PHP — no Blade partial dependency)
            'residoMenuHtml' => HeaderTemplate::buildResidoMenuHtml($menuTree),
            'residoActionsHtml' => HeaderTemplate::buildResidoActionsHtml(),
        ];

        return HeaderTemplate::safeSubstitute($this->html, $data);
    }

    public function livePreviewCss(): string
    {
        if (! $this->css) {
            return '';
        }

        return HeaderTemplate::safeSubstitute($this->css, [
            'headerBgColor' => $this->headerBgColor,
            'headerTextColor' => $this->headerTextColor,
            'headerBorderColor' => $this->headerBorderColor,
            'headerHeight' => $this->headerHeight,
            'shadowColor' => $this->shadowColor,
            'borderRadius' => $this->borderRadius,
            'fontFamily' => $this->fontFamily,
            'navFontSize' => $this->navFontSize,
            'navFontWeight' => $this->navFontWeight,
            'navItemPaddingX' => $this->navItemPaddingX,
            'navItemPaddingY' => $this->navItemPaddingY,
            'logoHeight' => $this->logoHeight,
            'logoWidth' => $this->logoWidth,
            'mobileBreakpoint' => $this->mobileBreakpoint,
            'navJustify' => match ($this->menuPosition) {
                'left' => 'flex-start',
                'center' => 'center',
                default => 'flex-end',
            },
            'dropdownEnterTransform' => match ($this->dropdownHoverStyle) {
                'slide' => 'translateY(-10px)',
                'scale' => 'scale(0.94)',
                default => 'translateY(-6px) scale(0.97)',
            },
        ]);
    }

    public function livePreviewDocument(): string
    {
        $css = $this->livePreviewCss();
        $html = $this->livePreviewHtml();
        $js = $this->js;

        return '<!doctype html><html><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<style>html,body{margin:0;padding:0;background:#fff;}</style>'
            .($css ? '<style>'.$css.'</style>' : '')
            .'</head><body>'
            .$html
            .($js ? '<script>'.$js.'</script>' : '')
            .'</body></html>';
    }

    private function loadSettings(array $settings): void
    {
        $this->sticky = $settings['sticky'] ?? true;
        $this->transparent = $settings['transparent'] ?? false;
        $this->headerHeight = $settings['header_height'] ?? 80;
        $this->headerBgColor = $settings['header_bg_color'] ?? '#ffffff';
        $this->headerTextColor = $settings['header_text_color'] ?? '#1f2937';
        $this->headerBorderColor = $settings['header_border_color'] ?? '#e5e7eb';
        $this->dropdownHoverStyle = $settings['dropdown_hover_style'] ?? 'fade';
        $this->menuPosition = $settings['menu_position'] ?? 'right';
        $this->showCta = $settings['show_cta'] ?? true;
        $this->ctaLoginLabel = $settings['cta_login_label'] ?? 'Login';
        $this->ctaRegisterLabel = $settings['cta_register_label'] ?? 'Register';
        $this->ctaContactLabel = $settings['cta_contact_label'] ?? 'Contact Us';
        $this->ctaContactUrl = $settings['cta_contact_url'] ?? '/contact';
        $this->showSearch = $settings['show_search'] ?? false;
        $this->fontFamily = $settings['font_family'] ?? "'Inter', system-ui, -apple-system, sans-serif";
        $this->navFontSize = $settings['nav_font_size'] ?? 14;
        $this->navFontWeight = $settings['nav_font_weight'] ?? 500;
        $this->navItemPaddingX = $settings['nav_item_padding_x'] ?? 16;
        $this->navItemPaddingY = $settings['nav_item_padding_y'] ?? 8;
        $this->logoHeight = $settings['logo_height'] ?? 36;
        $this->logoWidth = $settings['logo_width'] ?? 140;
        $this->borderRadius = $settings['border_radius'] ?? 8;
        $this->shadowEnabled = $settings['shadow_enabled'] ?? true;
        $this->shadowColor = $settings['shadow_color'] ?? 'rgba(0,0,0,0.08)';
        $this->mobileBreakpoint = $settings['mobile_breakpoint'] ?? 991;
    }

    private function collectSettings(): array
    {
        return [
            'sticky' => $this->sticky,
            'transparent' => $this->transparent,
            'header_height' => $this->headerHeight,
            'header_bg_color' => $this->headerBgColor,
            'header_text_color' => $this->headerTextColor,
            'header_border_color' => $this->headerBorderColor,
            'dropdown_hover_style' => $this->dropdownHoverStyle,
            'menu_position' => $this->menuPosition,
            'show_cta' => $this->showCta,
            'cta_login_label' => $this->ctaLoginLabel,
            'cta_register_label' => $this->ctaRegisterLabel,
            'cta_contact_label' => $this->ctaContactLabel,
            'cta_contact_url' => $this->ctaContactUrl,
            'show_search' => $this->showSearch,
            'font_family' => $this->fontFamily,
            'nav_font_size' => $this->navFontSize,
            'nav_font_weight' => $this->navFontWeight,
            'nav_item_padding_x' => $this->navItemPaddingX,
            'nav_item_padding_y' => $this->navItemPaddingY,
            'logo_height' => $this->logoHeight,
            'logo_width' => $this->logoWidth,
            'border_radius' => $this->borderRadius,
            'shadow_enabled' => $this->shadowEnabled,
            'shadow_color' => $this->shadowColor,
            'mobile_breakpoint' => $this->mobileBreakpoint,
        ];
    }

    public function render()
    {
        return view('livewire.admin.headers.manager')
            ->extends('admin.layouts.app')
            ->section('content');
    }
}
