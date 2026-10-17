<?php

namespace App\Livewire\Admin\Projects;

use App\Models\Project;
use Livewire\Component;

class Show extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render()
    {
        $this->project->loadMissing(['blocks.media', 'amenities']);

        return view('livewire.admin.projects.show', [
            'project' => $this->project,
            'gallery' => $this->project->getMedia('gallery'),
            'videos' => $this->project->getMedia('videos'),
        ])->extends('admin.layouts.app')->section('content');
    }
}
