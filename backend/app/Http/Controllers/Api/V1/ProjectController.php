<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Expense;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['sometimes', Rule::enum(ProjectStatus::class)]]);

        $spent = Expense::query()
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->selectRaw('project_id, sum(amount_cents) as total')
            ->pluck('total', 'project_id');

        return ProjectResource::collection(
            Project::query()
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->orderBy('name')
                ->get()
                ->each(fn (Project $project) => $project->spent_cents = (int) ($spent[$project->id] ?? 0)),
        );
    }

    public function store(StoreProjectRequest $request)
    {
        return new ProjectResource(Project::query()->create($request->validated()));
    }

    public function show(Project $project)
    {
        $project->spent_cents = (int) Expense::query()->where('project_id', $project->id)->sum('amount_cents');

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
