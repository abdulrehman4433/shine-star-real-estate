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
        $this->project->loadMissing(['blocks.plotSizes.block', 'plotSizes.block', 'amenities']);

        return view('livewire.admin.projects.show', [
            'project' => $this->project,
            'gallery' => $this->project->getMedia('gallery'),
            'unassignedPlotSizes' => $this->project->plotSizes->whereNull('project_block_id'),
        ])->extends('admin.layouts.app')->section('content');
    }
}
