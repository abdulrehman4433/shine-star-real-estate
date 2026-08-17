<?php

namespace App\Livewire\Admin\PageTemplates;

use App\Livewire\Concerns\Notifies;
use App\Models\PageTemplate;
use Illuminate\Support\Str;
use Livewire\Component;

class Manager extends Component
{
    use Notifies;

    public ?int $editingId = null;

    public string $name = '';

    public string $status = 'draft';

    public string $html = '';

    public string $css = '';

    public string $js = '';

    // Settings
    public int $containerWidth = 1280;

    public string $contentFontFamily = "'Inter', system-ui, -apple-system, sans-serif";

    public int $contentFontSize = 16;

    public float $contentLineHeight = 1.75;

    public string $contentTextColor = '#374151';

    public string $contentBgColor = '#ffffff';

    public string $headingFontFamily = "'Inter', system-ui, -apple-system, sans-serif";

    public string $headingColor = '#111827';

    public int $headingFontWeight = 700;

    public bool $showTitle = true;

    public string $titleAlignment = 'left';

    public string $titleBgColor = '#f8fafc';

    public int $titlePaddingY = 60;

    public int $borderRadius = 12;

    public bool $shadowEnabled = false;

    public string $shadowColor = 'rgba(0,0,0,0.08)';

    public bool $enableAnimation = true;

    public string $animationStyle = 'fade-up';

    public int $mobileBreakpoint = 768;

    // UI state
    public bool $showEditor = false;

    public string $activeEditorTab = 'html';

    public string $previewDevice = 'desktop';

    public bool $showImportModal = false;

    public string $importJson = '';

    public function createTemplate(): void
    {
        $this->resetForm();
        $this->html = $this->defaultHtml();
        $this->css = $this->defaultCss();
        $this->js = $this->defaultJs();
        $this->showEditor = true;
    }

    public function editTemplate(int $id): void
    {
        $template = PageTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->status = $template->status;
        $this->html = $template->html ?? $this->defaultHtml();
        $this->css = $template->css ?? $this->defaultCss();
        $this->js = $template->js ?? '';

        $settings = $template->getSettingsWithDefaults();
        $this->loadSettings($settings);

        $this->showEditor = true;
    }

    public function duplicateTemplate(int $id): void
    {
        $original = PageTemplate::findOrFail($id);
        $copy = $original->replicate();
        $copy->name = $original->name . ' (Copy)';
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->save();

        PageTemplate::forgetActiveCache();
    }

    public function exportTemplate(int $id): void
    {
        $template = PageTemplate::findOrFail($id);
        $data = [
            'name' => $template->name,
            'html' => $template->html,
            'css' => $template->css,
            'js' => $template->js,
            'settings' => $template->settings,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $filename = Str::slug($template->name) . '-page-template.json';

        $this->dispatch('download-template', json: $json, filename: $filename);
    }

    public function showImport(): void
    {
        $this->importJson = '';
        $this->showImportModal = true;
    }

    public function importTemplate(): void
    {
        $this->validate([
            'importJson' => 'required|json',
        ]);

        $data = json_decode($this->importJson, true);

        if (! isset($data['html'])) {
            $this->addError('importJson', 'Invalid template JSON: missing "html" field.');
            return;
        }

        if (! is_array($data['settings'] ?? null) && isset($data['settings'])) {
            $this->addError('importJson', 'Invalid template JSON: "settings" must be an object.');
            return;
        }

        if (! $this->validateMarkup($data['html'] ?? '', $data['css'] ?? '', $data['js'] ?? '', 'importJson')) {
            return;
        }

        $template = PageTemplate::create([
            'name' => $data['name'] ?? 'Imported Template',
            'status' => 'draft',
            'is_active' => false,
            'html' => $data['html'] ?? '',
            'css' => $data['css'] ?? '',
            'js' => $data['js'] ?? '',
            'settings' => $data['settings'] ?? null,
        ]);

        $this->showImportModal = false;

        PageTemplate::forgetActiveCache();
    }

    public function setActive(int $id): void
    {
        PageTemplate::where('is_active', true)->update(['is_active' => false]);

        $template = PageTemplate::findOrFail($id);
        $template->update([
            'is_active' => true,
            'status' => 'published',
        ]);

        $this->status = 'published';

        PageTemplate::forgetActiveCache();

        $this->notifySuccess("\"{$template->name}\" is now the active page template.");
    }

    public function deleteTemplate(int $id): void
    {
        $template = PageTemplate::findOrFail($id);
        $title = $template->name;
        $wasActive = $template->is_active;
        $template->delete();

        if ($wasActive) {
            PageTemplate::forgetActiveCache();
        }

        $this->notifySuccess("\"{$title}\" deleted.");
    }

    public function saveDraft(): void
    {
        $this->status = 'draft';

        if ($this->save()) {
            $this->notifySuccess("\"{$this->name}\" saved as draft.");
        }
    }

    public function publish(): void
    {
        $this->status = 'published';

        if ($this->save()) {
            $this->notifySuccess("\"{$this->name}\" published.");
        }
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
            $this->addError('html', 'Cannot publish a page template with empty HTML content.');
            $this->notifyError('Cannot publish a page template with empty HTML content.');
            return false;
        }

        if (! $this->validateMarkup($this->html, $this->css, $this->js, 'html')) {
            $this->notifyError('Template was not saved. Please fix the highlighted errors.');
            return false;
        }

        $template = $this->editingId
            ? PageTemplate::findOrFail($this->editingId)
            : new PageTemplate();

        $settings = $this->collectSettings();

        $template->fill([
            'name' => $this->name,
            'status' => $this->status,
            'html' => $this->html,
            'css' => $this->css,
            'js' => $this->js,
            'settings' => $settings,
        ])->save();

        PageTemplate::forgetActiveCache();

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
                $this->addError($errorField, 'Template HTML is not well-formed: '.trim($first->message));
                return false;
            }
        }

        if ($css !== '' && substr_count($css, '{') !== substr_count($css, '}')) {
            $this->addError($errorField === 'importJson' ? 'importJson' : 'css', 'Template CSS has unbalanced { } braces.');
            return false;
        }

        if ($js !== '') {
            foreach (['{' => '}', '(' => ')', '[' => ']'] as $open => $close) {
                if (substr_count($js, $open) !== substr_count($js, $close)) {
                    $this->addError($errorField === 'importJson' ? 'importJson' : 'js', "Template JS has unbalanced {$open} {$close} pairs.");
                    return false;
                }
            }
        }

        return true;
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->resetForm();
    }

    /**
     * Generates a full preview document and opens it in a new browser tab.
     */
    public function previewTemplate(): void
    {
        $document = $this->livePreviewDocument();
        $this->dispatch('open-preview', document: $document);
    }

    public function switchEditorTab(string $tab): void
    {
        $this->activeEditorTab = $tab;
    }

    public function setPreviewDevice(string $device): void
    {
        $this->previewDevice = $device;
    }

    private function loadSettings(array $settings): void
    {
        $this->containerWidth = $settings['container_width'] ?? 1280;
        $this->contentFontFamily = $settings['content_font_family'] ?? "'Inter', system-ui, -apple-system, sans-serif";
        $this->contentFontSize = $settings['content_font_size'] ?? 16;
        $this->contentLineHeight = $settings['content_line_height'] ?? 1.75;
        $this->contentTextColor = $settings['content_text_color'] ?? '#374151';
        $this->contentBgColor = $settings['content_bg_color'] ?? '#ffffff';
        $this->headingFontFamily = $settings['heading_font_family'] ?? "'Inter', system-ui, -apple-system, sans-serif";
        $this->headingColor = $settings['heading_color'] ?? '#111827';
        $this->headingFontWeight = $settings['heading_font_weight'] ?? 700;
        $this->showTitle = $settings['show_title'] ?? true;
        $this->titleAlignment = $settings['title_alignment'] ?? 'left';
        $this->titleBgColor = $settings['title_bg_color'] ?? '#f8fafc';
        $this->titlePaddingY = $settings['title_padding_y'] ?? 60;
        $this->borderRadius = $settings['border_radius'] ?? 12;
        $this->shadowEnabled = $settings['shadow_enabled'] ?? false;
        $this->shadowColor = $settings['shadow_color'] ?? 'rgba(0,0,0,0.08)';
        $this->enableAnimation = $settings['enable_animation'] ?? true;
        $this->animationStyle = $settings['animation_style'] ?? 'fade-up';
        $this->mobileBreakpoint = $settings['mobile_breakpoint'] ?? 768;
    }

    /**
     * Live preview of the currently-edited (possibly unsaved) HTML.
     */
    public function livePreviewHtml(): string
    {
        if (! $this->html) {
            return '';
        }

        $data = [
            'pageTitle' => 'Sample Page Title',
            'pageSlug' => 'sample-page',
            'pageMetaTitle' => 'Sample Page | Shine Star Marketing',
            'pageMetaDescription' => 'A sample page preview.',
            'pageContent' => '<h2>Welcome to Your Page</h2>
<p>This is a live preview of your page template. <a href="#">Links are styled</a> with your brand colors.</p>
<blockquote>Blockquotes stand out with your gold accent.</blockquote>
<h3>Level 3 Heading</h3>
<p>Paragraphs, images, and all content blocks render according to your template settings.</p>
<ul><li>Unordered list items</li><li>Clean spacing and typography</li></ul>',
            'containerWidth' => $this->containerWidth.'px',
            'titleAlignment' => $this->titleAlignment,
            'showTitle' => $this->showTitle,
            'enableAnimation' => false,
            'animationStyle' => $this->animationStyle,
        ];

        return PageTemplate::safeSubstitute($this->html, $data);
    }

    /**
     * Live preview of the currently-edited CSS.
     */
    public function livePreviewCss(): string
    {
        if (! $this->css) {
            return '';
        }

        return PageTemplate::safeSubstitute($this->css, [
            'containerWidth' => $this->containerWidth.'px',
            'contentFontFamily' => $this->contentFontFamily,
            'contentFontSize' => $this->contentFontSize.'px',
            'contentLineHeight' => $this->contentLineHeight,
            'contentTextColor' => $this->contentTextColor,
            'contentBgColor' => $this->contentBgColor,
            'headingFontFamily' => $this->headingFontFamily,
            'headingColor' => $this->headingColor,
            'headingFontWeight' => $this->headingFontWeight,
            'titleAlignment' => $this->titleAlignment,
            'titlePaddingY' => $this->titlePaddingY.'px',
            'titleBgColor' => $this->titleBgColor,
            'borderRadius' => $this->borderRadius.'px',
            'shadowEnabled' => $this->shadowEnabled,
            'shadowColor' => $this->shadowColor,
            'mobileBreakpoint' => $this->mobileBreakpoint.'px',
        ]);
    }

    /**
     * Full standalone HTML document for the preview iframe srcdoc.
     */
    public function livePreviewDocument(): string
    {
        $css = $this->livePreviewCss();
        $html = $this->livePreviewHtml();
        $js = $this->js;

        return '<!doctype html><html><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<style>html,body{margin:0;padding:0;background:#f9fafb;}</style>'
            .($css ? '<style>'.$css.'</style>' : '')
            .'</head><body>'
            .$html
            .($js ? '<script>'.$js.'</script>' : '')
            .'</body></html>';
    }

    private function collectSettings(): array
    {
        return [
            'container_width' => $this->containerWidth,
            'content_font_family' => $this->contentFontFamily,
            'content_font_size' => $this->contentFontSize,
            'content_line_height' => $this->contentLineHeight,
            'content_text_color' => $this->contentTextColor,
            'content_bg_color' => $this->contentBgColor,
            'heading_font_family' => $this->headingFontFamily,
            'heading_color' => $this->headingColor,
            'heading_font_weight' => $this->headingFontWeight,
            'show_title' => $this->showTitle,
            'title_alignment' => $this->titleAlignment,
            'title_bg_color' => $this->titleBgColor,
            'title_padding_y' => $this->titlePaddingY,
            'border_radius' => $this->borderRadius,
            'shadow_enabled' => $this->shadowEnabled,
            'shadow_color' => $this->shadowColor,
            'enable_animation' => $this->enableAnimation,
            'animation_style' => $this->animationStyle,
            'mobile_breakpoint' => $this->mobileBreakpoint,
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'status',
            'containerWidth', 'contentFontFamily', 'contentFontSize', 'contentLineHeight',
            'contentTextColor', 'contentBgColor', 'headingFontFamily', 'headingColor',
            'headingFontWeight', 'showTitle', 'titleAlignment', 'titleBgColor', 'titlePaddingY',
            'borderRadius', 'shadowEnabled', 'shadowColor', 'enableAnimation', 'animationStyle',
            'mobileBreakpoint',
        ]);
    }

    private function defaultHtml(): string
    {
        return PageTemplate::defaultHtmlTemplate();
    }

    private function defaultCss(): string
    {
        return PageTemplate::defaultCssTemplate();
    }

    private function defaultJs(): string
    {
        return PageTemplate::defaultJsTemplate();
    }

    public function render()
    {
        return view('livewire.admin.page-templates.manager', [
            'templates' => PageTemplate::orderBy('id', 'desc')->get(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
