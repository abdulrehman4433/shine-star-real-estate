<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Jobs\GenerateSitemap;
use App\Livewire\Concerns\Notifies;
use App\Models\Page;
use Livewire\Component;

class Manager extends Component
{
    use Notifies;

    public bool $showTrash = false;

    public function toggleTrashView(): void
    {
        $this->showTrash = ! $this->showTrash;
    }

    public function createPage()
    {
        $page = Page::create([
            'title' => 'Untitled Page',
            'status' => PageStatus::Draft->value,
            'order' => (Page::max('order') ?? -1) + 1,
        ]);

        return redirect()->route('admin.pages.edit', $page);
    }

    public function toggleStatus(int $id): void
    {
        $page = Page::findOrFail($id);
        $willBePublished = ! $page->isPublished();
        $page->update([
            'status' => $willBePublished ? PageStatus::Published->value : PageStatus::Draft->value,
        ]);

        GenerateSitemap::dispatch();

        $this->notifySuccess($willBePublished ? "\"{$page->title}\" published." : "\"{$page->title}\" unpublished.");
    }

    /** Soft delete — the page moves to Trash and can be restored later, see restore()/forceDelete(). */
    public function delete(int $id): void
    {
        $page = Page::findOrFail($id);
        $title = $page->title;
        $page->delete();

        GenerateSitemap::dispatch();

        $this->notifySuccess("\"{$title}\" moved to trash.");
    }

    public function restore(int $id): void
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();

        GenerateSitemap::dispatch();

        $this->notifySuccess("\"{$page->title}\" restored.");
    }

    /** Permanent, unrecoverable delete — only ever reachable from the Trash view. */
    public function forceDelete(int $id): void
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $title = $page->title;
        $page->forceDelete();

        $this->notifySuccess("\"{$title}\" permanently deleted.");
    }

    public function reorder(int $itemId, int $position): void
    {
        $item = Page::findOrFail($itemId);

        $siblings = Page::orderBy('order')
            ->get()
            ->reject(fn (Page $p) => $p->id === $item->id)
            ->values();

        $siblings->splice($position, 0, [$item]);

        foreach ($siblings as $index => $sibling) {
            if ($sibling->order !== $index) {
                $sibling->update(['order' => $index]);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.pages.manager', [
            'pages' => $this->showTrash
                ? Page::onlyTrashed()->latest('deleted_at')->get()
                : Page::orderBy('order')->get(),
            'trashedCount' => Page::onlyTrashed()->count(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
