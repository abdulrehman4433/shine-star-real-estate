<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function show(string $slug)
    {
        // Exclude home pages (null slug) — they are handled by HomeController
        $page = Page::query()
            ->whereNotNull('slug')
            ->where('slug', $slug)
            ->firstOrFail();

        $canPreviewDrafts = Auth::check() && Auth::user()->hasAnyRole(['admin', 'super-admin']);

        abort_if(! $page->isPublished() && ! $canPreviewDrafts, 404);

        $page->load(['blocks.media', 'pageTemplate']);

        // If the page has its own custom HTML, render using the inline code editors
        if (! empty($page->html)) {
            $pageData = [
                'title' => $page->title,
                'pageContent' => $page->content ?? '',
                // Generic plumbing for any plain-HTML page embedding a real POST form (currently just
                // /contact-us) — harmless, unused placeholders on every other custom-HTML page, since
                // safeSubstitute() just leaves an unreferenced {{ $var }} out of the stored HTML alone.
                'csrfToken' => csrf_token(),
                'contactStatusHtml' => $this->contactStatusHtml(),
                'oldName' => e(old('name', '')),
                'oldEmail' => e(old('email', '')),
                'oldMessage' => e(old('message', '')),
            ];

            $viewData = [
                'page' => $page,
                'templateHtml' => $page->renderHtml($pageData),
                'templateCss' => $page->renderCss(),
                'templateJs' => $page->renderJs(),
            ];

            return view('frontend.pages.show-template', $viewData);
        }

        // Fall back to page template if set (for backward compatibility)
        if ($page->pageTemplate && $page->pageTemplate->status === 'published') {
            $blocksContent = view('frontend.pages.render-blocks', ['blocks' => $page->blocks])->render();

            $pageData = [
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title ?? '',
                'meta_description' => $page->meta_description ?? '',
                'pageContent' => $blocksContent,
            ];

            $viewData = [
                'page' => $page,
                'templateHtml' => $page->pageTemplate->renderHtml($pageData),
                'templateCss' => $page->pageTemplate->renderCss(),
                'templateJs' => $page->pageTemplate->renderJs(),
            ];

            return view('frontend.pages.show-template', $viewData);
        }

        // Legacy fallback: render blocks directly
        return view('frontend.pages.show', compact('page'));
    }

    /** Pre-rendered success/validation-error markup for the plain-HTML contact form — the stored Page
     *  HTML can't contain @if directives (safeSubstitute() only does `{{ $var }}`/`{!! $var !!}`
     *  substitution, see Page::renderHtml()'s doc comment), so the conditional logic has to happen here
     *  in PHP and get handed over as a single pre-built HTML string instead. */
    private function contactStatusHtml(): string
    {
        if (session('contact_success')) {
            return '<div class="alert alert-success">Thanks for reaching out! We\'ll get back to you soon.</div>';
        }

        $errors = session('errors');

        if ($errors && $errors->any()) {
            $items = collect($errors->all())
                ->map(fn ($m) => '<li>'.e($m).'</li>')
                ->implode('');

            return '<div class="alert alert-danger"><ul class="mb-0">'.$items.'</ul></div>';
        }

        return '';
    }
}
