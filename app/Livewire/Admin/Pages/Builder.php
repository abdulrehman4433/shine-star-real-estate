<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\Page;
use App\Models\PageTemplate;
use Livewire\Component;

class Builder extends Component
{
    use Notifies;

    public Page $page;

    public string $title = '';

    public ?string $slug = null;

    public string $status = 'draft';

    public string $metaTitle = '';

    public string $metaDescription = '';

    public string $seoMetaKeywords = '';

    public string $seoCanonicalUrl = '';

    // Code editor fields
    public string $html = '';

    public string $css = '';

    public string $js = '';

    // UI state
    public string $activeEditorTab = 'html';

    public function mount(Page $page): void
    {
        $this->page = $page;
        $this->title = $page->title;
        $this->slug = $page->slug ?? null;
        $this->status = $page->status;
        $this->metaTitle = (string) $page->meta_title;
        $this->metaDescription = (string) $page->meta_description;
        $this->seoMetaKeywords = (string) $page->seo?->meta_keywords;
        $this->seoCanonicalUrl = (string) $page->seo?->canonical_url;

        // Code editor content
        $this->html = $page->html ?? '';
        $this->css = $page->css ?? '';
        $this->js = $page->js ?? '';
    }

    public function saveMeta(): void
    {
        $resolvedSlug = $this->slug ?: null;

        $this->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'alpha_dash',
                'max:255',
                function ($attribute, $value, $fail) use ($resolvedSlug) {
                    if ($resolvedSlug === null) {
                        // Empty slug → home page. Only one published home page allowed.
                        $existingHome = Page::query()
                            ->whereNull('slug')
                            ->where('status', PageStatus::Published->value)
                            ->where('id', '!=', $this->page->id)
                            ->exists();

                        if ($existingHome) {
                            $fail('A published home page already exists. Please unpublish it first before setting another page as the home page.');
                        }
                    } else {
                        // Non-empty slug: check whether another *published* page already uses it.
                        $conflict = Page::query()
                            ->where('slug', $resolvedSlug)
                            ->where('status', PageStatus::Published->value)
                            ->where('id', '!=', $this->page->id)
                            ->exists();

                        if ($conflict) {
                            $fail('This URL slug is already in use by a published page. Please unpublish that page first before assigning this slug.');
                        }

                        // Also enforce uniqueness across ALL pages (including draft) via a standard unique rule.
                        // We do this with a separate check so the error message is clearer.
                        $otherDraft = Page::query()
                            ->where('slug', $resolvedSlug)
                            ->whereIn('status', [PageStatus::Draft->value])
                            ->where('id', '!=', $this->page->id)
                            ->exists();

                        if ($otherDraft) {
                            $fail('Another page (draft) already uses this slug. Please delete or rename that page first.');
                        }
                    }
                },
            ],
            'status' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, PageStatus::cases())),
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:255',
            'seoMetaKeywords' => 'nullable|string|max:255',
            'seoCanonicalUrl' => 'nullable|string|max:255',
        ]);

        $this->page->update([
            'title' => $this->title,
            'slug' => $resolvedSlug,
            'status' => $this->status,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'html' => $this->html,
            'css' => $this->css,
            'js' => $this->js,
        ]);

        $this->page->updateSeo([
            'meta_keywords' => $this->seoMetaKeywords ?: null,
            'canonical_url' => $this->seoCanonicalUrl ?: null,
        ]);

        GenerateSitemap::dispatch();

        $this->notifySuccess('Page saved.');
    }

    public function saveDraft(): void
    {
        $this->status = 'draft';
        $this->saveMeta();
    }

    public function publish(): void
    {
        $this->status = 'published';
        $this->saveMeta();
    }

    public function switchEditorTab(string $tab): void
    {
        $this->activeEditorTab = $tab;
    }

    /**
     * Generates a full preview document and opens it in a new browser tab.
     */
    public function previewTemplate(): void
    {
        $document = $this->livePreviewDocument();
        $this->dispatch('open-preview', document: $document);
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
            'pageTitle' => $this->title ?: 'Sample Page Title',
            'pageSlug' => $this->slug ?: 'sample-page',
            'pageMetaTitle' => $this->metaTitle ?: 'Sample Page | Shine Star Marketing',
            'pageMetaDescription' => $this->metaDescription ?: 'A sample page preview.',
            'pageContent' => '<h2>Welcome to Your Page</h2>
<p>This is a live preview of your page. <a href="#">Links are styled</a> with your brand colors.</p>
<blockquote>Blockquotes stand out with your gold accent.</blockquote>
<h3>Level 3 Heading</h3>
<p>Paragraphs, images, and all content blocks render according to your page settings.</p>
<ul><li>Unordered list items</li><li>Clean spacing and typography</li></ul>',
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

        return PageTemplate::safeSubstitute($this->css, []);
    }

    /**
     * Full standalone HTML document for the preview.
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

    public function render()
    {
        return view('livewire.admin.pages.builder')
            ->extends('admin.layouts.app')
            ->section('content');
    }
}
