<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectsController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->withCount(['blocks', 'plotSizes'])
            ->active()
            ->orderBy('order')
            ->paginate(9);

        return view('frontend.projects.index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        $canPreview = Auth::check() && Auth::user()->hasAnyRole(['admin', 'super-admin']);

        abort_if(! $project->is_active && ! $canPreview, 404);

        $project->load(['blocks.plotSizes', 'plotSizes.block', 'amenities', 'media']);

        $otherProjects = Project::query()
            ->where('id', '!=', $project->id)
            ->active()
            ->orderBy('order')
            ->take(8)
            ->get();

        return view('frontend.projects.show', [
            'project' => $project,
            'unassignedPlotSizes' => $project->plotSizes->whereNull('project_block_id'),
            'otherProjects' => $otherProjects,
        ]);
    }
}
