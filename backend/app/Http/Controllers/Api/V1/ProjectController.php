<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['sometimes', Rule::enum(ProjectStatus::class)]]);

        return ProjectResource::collection(
            Project::query()
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreProjectRequest $request)
    {
        return new ProjectResource(Project::query()->create($request->validated()));
    }

    public function show(Project $project)
    {
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return new ProjectResource($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->noContent();
    }
}
