<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'tag' => ['sometimes', 'string', 'max:255'],
            'due_before' => ['sometimes', 'date'],
            'sort' => ['sometimes', Rule::in(['due_date', '-due_date', 'created_at', '-created_at'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $filters['sort'] ?? 'due_date';
        [$column, $direction] = str_starts_with($sort, '-')
            ? [substr($sort, 1), 'desc']
            : [$sort, 'asc'];

        $tasks = Task::query()
            ->with('tags')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['project_id'] ?? null, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters['tag'] ?? null, fn ($query, $tag) => $query->whereHas(
                'tags', fn ($w) => $w->where('name', $tag),
            ))
            ->when($filters['due_before'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '<=', $date))
            ->orderBy($column, $direction)
            ->paginate($filters['per_page'] ?? 25);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $task = Task::query()->create($data + ['status' => $data['status'] ?? TaskStatus::Open, 'priority' => $data['priority'] ?? TaskPriority::Medium]);

        if ($tags !== null) {
            $task->syncTagNames($tags);
        }

        return new TaskResource($task->load('tags'));
    }

    public function show(Task $task)
    {
        return new TaskResource($task->load('tags'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $task->update($data);

        if ($tags !== null) {
            $task->syncTagNames($tags);
        }

        return new TaskResource($task->load('tags'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }

    public function complete(Task $task)
    {
        if ($task->status === TaskStatus::Done) {
            return response()->json(['message' => 'Task is already completed.'], 409);
        }

        $next = $task->complete();

        return response()->json([
            'data' => new TaskResource($task->load('tags')),
            'next' => $next ? new TaskResource($next->load('tags')) : null,
        ]);
    }
}
