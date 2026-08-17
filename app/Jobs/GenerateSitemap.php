<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $sitemap = Sitemap::create()
            ->add(Url::create('/')->setPriority(1.0))
            ->add(Url::create('/properties')->setPriority(0.9))
            ->add(Url::create('/blog')->setPriority(0.7));

        Property::query()->approved()->each(function (Property $property) use ($sitemap) {
            $sitemap->add(
                Url::create(route('properties.show', $property))
                    ->setLastModificationDate($property->updated_at)
                    ->setPriority(0.8)
            );
        });

        Page::query()->published()->each(function (Page $page) use ($sitemap) {
            $sitemap->add(
                Url::create(url($page->slug))
                    ->setLastModificationDate($page->updated_at)
                    ->setPriority(0.6)
            );
        });

        BlogPost::query()->published()->each(function (BlogPost $post) use ($sitemap) {
            $sitemap->add(
                Url::create(route('blog.show', $post))
                    ->setLastModificationDate($post->updated_at)
                    ->setPriority(0.6)
            );
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
