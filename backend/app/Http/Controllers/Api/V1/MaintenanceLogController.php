<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceLogRequest;
use App\Http\Requests\UpdateMaintenanceLogRequest;
use App\Http\Resources\MaintenanceLogResource;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;

class MaintenanceLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'item_id' => ['sometimes', 'integer', 'exists:items,id'],
            'maintenance_plan_id' => ['sometimes', 'integer', 'exists:maintenance_plans,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = MaintenanceLog::query()
            ->with('item')
            ->when($filters['item_id'] ?? null, fn ($query, $id) => $query->where('item_id', $id))
            ->when($filters['maintenance_plan_id'] ?? null, fn ($query, $id) => $query->where('maintenance_plan_id', $id))
            ->orderByDesc('performed_on')
            ->paginate($filters['per_page'] ?? 25);

        return MaintenanceLogResource::collection($logs);
    }

    public function store(StoreMaintenanceLogRequest $request)
    {
        $log = MaintenanceLog::query()->create($request->validated());

        // Work performed against a plan pushes its schedule forward.
        if ($log->maintenance_plan_id !== null) {
            $log->plan->advanceFrom($log->performed_on);
        }

        return new MaintenanceLogResource($log);
    }

    public function show(MaintenanceLog $maintenanceLog)
    {
        return new MaintenanceLogResource($maintenanceLog->load('item'));
    }

    public function update(UpdateMaintenanceLogRequest $request, MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->update($request->validated());

        return new MaintenanceLogResource($maintenanceLog);
    }

    public function destroy(MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->delete();

        return response()->noContent();
    }
}
