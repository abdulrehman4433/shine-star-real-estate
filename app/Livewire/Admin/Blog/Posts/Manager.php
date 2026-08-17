<?php

namespace App\Livewire\Admin\Blog\Posts;

use App\Enums\BlogPostStatus;
use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\BlogPost;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use Notifies, WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $post = BlogPost::findOrFail($id);

        $publishing = ! $post->isPublished();

        $post->update([
            'status' => $publishing ? BlogPostStatus::Published->value : BlogPostStatus::Draft->value,
            'published_at' => $publishing ? ($post->published_at ?? now()) : $post->published_at,
        ]);

        GenerateSitemap::dispatch();

        $this->notifySuccess($publishing ? 'Post published.' : 'Post unpublished.');
    }

    public function delete(int $id): void
    {
        $post = BlogPost::findOrFail($id);
        $title = $post->title;
        $post->delete();

        GenerateSitemap::dispatch();

        $this->notifySuccess("\"{$title}\" deleted.");
    }

    public function render()
    {
        $posts = BlogPost::query()
            ->with(['category', 'author'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.blog.posts.manager', [
            'posts' => $posts,
            'statuses' => BlogPostStatus::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
