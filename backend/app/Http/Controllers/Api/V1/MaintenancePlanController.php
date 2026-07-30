<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenancePlanRequest;
use App\Http\Requests\UpdateMaintenancePlanRequest;
use App\Http\Resources\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'item_id' => ['sometimes', 'integer', 'exists:items,id'],
            'due_before' => ['sometimes', 'date'],
        ]);

        return MaintenancePlanResource::collection(
            MaintenancePlan::query()
                ->with('item')
                ->when($filters['item_id'] ?? null, fn ($query, $id) => $query->where('item_id', $id))
                ->when($filters['due_before'] ?? null, fn ($query, $date) => $query->whereDate('next_due_on', '<=', $date))
                ->orderBy('next_due_on')
                ->get(),
        );
    }

    public function store(StoreMaintenancePlanRequest $request)
    {
        return new MaintenancePlanResource(MaintenancePlan::query()->create($request->validated()));
    }

    public function show(MaintenancePlan $maintenancePlan)
    {
        return new MaintenancePlanResource($maintenancePlan->load('item'));
    }

    public function update(UpdateMaintenancePlanRequest $request, MaintenancePlan $maintenancePlan)
    {
        $maintenancePlan->update($request->validated());

        return new MaintenancePlanResource($maintenancePlan);
    }

    public function destroy(MaintenancePlan $maintenancePlan)
    {
        $maintenancePlan->delete();

        return response()->noContent();
    }
}
