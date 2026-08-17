<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\Review;
use App\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $featuredPropertiesHtml = $this->renderFeaturedPropertiesSection();
        $reviewsHtml = $this->renderReviewsSection();
        $projectsHtml = $this->renderProjectsSection();
        $heroCategoryOptionsHtml = $this->renderHeroCategoryOptions();

        // Check if a published home page (null slug) exists with custom content
        $homePage = Page::query()
            ->whereNull('slug')
            ->where('status', 'published')
            ->first();

        if ($homePage && ! empty($homePage->html)) {
            $pageData = [
                'title' => $homePage->title,
                'slug' => '',
                'pageContent' => '',
                'featuredPropertiesSection' => $featuredPropertiesHtml,
                'reviewsSection' => $reviewsHtml,
                'projectsSection' => $projectsHtml,
                'heroCategoryOptions' => $heroCategoryOptionsHtml,
                'propertiesUrl' => route('properties.index'),
            ];

            $viewData = [
                'page' => $homePage,
                'templateHtml' => $homePage->renderHtml($pageData),
                'templateCss' => $homePage->renderCss(),
                'templateJs' => $homePage->renderJs(),
            ];

            return view('frontend.pages.show-template', $viewData);
        }

        return view('frontend.home', [
            'featuredPropertiesHtml' => $featuredPropertiesHtml,
            'reviewsHtml' => $reviewsHtml,
            'projectsHtml' => $projectsHtml,
        ]);
    }

    private function renderFeaturedPropertiesSection(): string
    {
        if (! (bool) Setting::get('show_featured_properties', true)) {
            return '';
        }

        $properties = Property::query()
            ->with('type')
            ->approved()
            ->latest()
            ->take(6)
            ->get();

        return view('frontend.partials.featured-properties-section', [
            'properties' => $properties,
        ])->render();
    }

    private function renderReviewsSection(): string
    {
        if (! (bool) Setting::get('show_reviews_section', true)) {
            return '';
        }

        $reviews = Review::cachedActive();

        return view('frontend.partials.reviews-section', [
            'reviews' => $reviews,
        ])->render();
    }

    private function renderProjectsSection(): string
    {
        if (! (bool) Setting::get('show_projects_section', true)) {
            return '';
        }

        $projects = Project::query()
            ->withCount(['blocks', 'plotSizes'])
            ->active()
            ->featured()
            ->orderBy('order')
            ->take(6)
            ->get();

        return view('frontend.partials.projects-section', [
            'projects' => $projects,
        ])->render();
    }

    /** Real category tree for the hero search bar's <select> — the stored Page HTML can only ever
     *  reference a plain variable (see HeaderTemplate/PageTemplate's safeSubstitute()), so the options
     *  have to be pre-rendered here rather than looped with @foreach inside the stored template. Same
     *  parent+indented-children shape as WithPropertyFilters::categories()/the real /properties page's
     *  own category select, so picking one here and landing on /properties shows the identical tree. */
    private function renderHeroCategoryOptions(): string
    {
        $categories = PropertyCategory::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()])
            ->orderBy('order')
            ->get();

        return view('frontend.partials.hero-category-options', [
            'categories' => $categories,
        ])->render();
    }
}
