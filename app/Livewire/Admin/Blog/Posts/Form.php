<?php

namespace App\Livewire\Admin\Blog\Posts;

use App\Enums\BlogPostStatus;
use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use Notifies, WithFileUploads;

    public ?int $postId = null;

    public string $title = '';

    public string $categoryId = '';

    public array $tagIds = [];

    public string $newCategoryName = '';

    public string $newTagName = '';

    public string $excerpt = '';

    public string $content = '';

    public string $status = 'draft';

    public string $publishedAt = '';

    public $featuredImage = null;

    public string $seoMetaTitle = '';

    public string $seoMetaDescription = '';

    public string $seoMetaKeywords = '';

    public string $seoCanonicalUrl = '';

    public function mount(?BlogPost $post = null): void
    {
        if ($post && $post->exists) {
            $this->postId = $post->id;
            $this->title = $post->title;
            $this->categoryId = (string) $post->category_id;
            $this->tagIds = $post->tags->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->excerpt = (string) $post->excerpt;
            $this->content = (string) $post->content;
            $this->status = $post->status;
            $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->seoMetaTitle = (string) $post->seo?->meta_title;
            $this->seoMetaDescription = (string) $post->seo?->meta_description;
            $this->seoMetaKeywords = (string) $post->seo?->meta_keywords;
            $this->seoCanonicalUrl = (string) $post->seo?->canonical_url;
        }
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'categoryId' => 'nullable|exists:blog_categories,id',
            'tagIds' => 'array',
            'tagIds.*' => 'exists:blog_tags,id',
            'excerpt' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'status' => 'required|in:'.implode(',', array_map(fn ($c) => $c->value, BlogPostStatus::cases())),
            'publishedAt' => 'nullable|date',
            'featuredImage' => 'nullable|image|max:4096',
            'seoMetaTitle' => 'nullable|string|max:255',
            'seoMetaDescription' => 'nullable|string|max:255',
            'seoMetaKeywords' => 'nullable|string|max:255',
            'seoCanonicalUrl' => 'nullable|string|max:255',
        ];
    }

    public function quickAddCategory(): void
    {
        $this->validate([
            'newCategoryName' => ['required', 'string', 'max:255', Rule::unique('blog_categories', 'name')],
        ]);

        $category = BlogCategory::create([
            'name' => $this->newCategoryName,
            'is_active' => true,
            'order' => (BlogCategory::max('order') ?? -1) + 1,
        ]);

        $this->categoryId = (string) $category->id;
        $this->newCategoryName = '';
    }

    public function quickAddTag(): void
    {
        $this->validate([
            'newTagName' => ['required', 'string', 'max:255', Rule::unique('blog_tags', 'name')],
        ]);

        $tag = BlogTag::create(['name' => $this->newTagName]);

        $this->tagIds[] = (string) $tag->id;
        $this->newTagName = '';
    }

    public function save()
    {
        $this->validate();

        $post = $this->postId
            ? BlogPost::findOrFail($this->postId)
            : new BlogPost(['author_id' => auth()->id()]);

        $post->fill([
            'category_id' => $this->categoryId ?: null,
            'title' => $this->title,
            'excerpt' => $this->excerpt ?: null,
            'content' => $this->content,
            'status' => $this->status,
            'published_at' => $this->resolvePublishedAt(),
        ])->save();

        $post->tags()->sync($this->tagIds);

        if ($this->featuredImage) {
            $post->addMedia($this->featuredImage->getRealPath())
                ->usingFileName($this->featuredImage->getClientOriginalName())
                ->toMediaCollection('featured_image');
        }

        $post->updateSeo([
            'meta_title' => $this->seoMetaTitle ?: null,
            'meta_description' => $this->seoMetaDescription ?: null,
            'meta_keywords' => $this->seoMetaKeywords ?: null,
            'canonical_url' => $this->seoCanonicalUrl ?: null,
        ]);

        GenerateSitemap::dispatch();

        $this->postId = $post->id;

        $this->flashSuccess('Post saved.');

        return redirect()->route('admin.blog.posts.index');
    }

    private function resolvePublishedAt(): ?string
    {
        if ($this->publishedAt) {
            return $this->publishedAt;
        }

        if ($this->status === BlogPostStatus::Published->value) {
            return now();
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin.blog.posts.form', [
            'categories' => BlogCategory::orderBy('order')->get(),
            'tags' => BlogTag::orderBy('name')->get(),
            'existingFeaturedImage' => $this->postId ? BlogPost::find($this->postId)?->featured_image_url : null,
        ])->extends('admin.layouts.app')->section('content');
    }
}
