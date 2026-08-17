<?php

namespace App\Livewire\Frontend\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Listing extends Component
{
    use WithPagination;

    #[Url]
    public string $category = '';

    #[Url]
    public string $tag = '';

    public function updating($property): void
    {
        if (in_array($property, ['category', 'tag'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['category', 'tag']);
        $this->resetPage();
    }

    public function render()
    {
        $posts = BlogPost::query()
            ->published()
            ->with(['category', 'author', 'tags', 'media'])
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->when($this->tag, fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('blog_tags.id', $this->tag)))
            ->latest('published_at')
            ->paginate(6);

        return view('livewire.frontend.blog.listing', [
            'posts' => $posts,
            'categories' => BlogCategory::active()->orderBy('order')->get(),
            'tags' => BlogTag::orderBy('name')->get(),
        ])->extends('frontend.layouts.app')->section('content');
    }
}
