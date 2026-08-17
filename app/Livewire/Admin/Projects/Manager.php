<?php

namespace App\Livewire\Admin\Projects;

use App\Enums\ProjectType;
use App\Livewire\Concerns\Notifies;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use Notifies, WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_active' => ! $project->is_active]);

        $this->notifySuccess($project->is_active ? 'Project is now visible.' : 'Project hidden.');
    }

    public function toggleFeatured(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_featured' => ! $project->is_featured]);

        $this->notifySuccess($project->is_featured ? 'Marked as featured.' : 'Featured removed.');
    }

    public function delete(int $id): void
    {
        $project = Project::findOrFail($id);
        $title = $project->title;
        $project->delete();

        $this->notifySuccess("\"{$title}\" deleted.");
    }

    public function render()
    {
        $projects = Project::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('society_name', 'like', "%{$this->search}%");
            }))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->withCount(['blocks', 'plotSizes'])
            ->orderBy('order')
            ->paginate(20);

        return view('livewire.admin.projects.manager', [
            'projects' => $projects,
            'types' => ProjectType::cases(),
        ])->extends('admin.layouts.app')->section('content');
    }
}
