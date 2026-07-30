<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LocationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['kind' => ['sometimes', Rule::enum(LocationKind::class)]]);

        // Full list, no pagination: the frontend builds the tree client-side.
        return LocationResource::collection(
            Location::query()
                ->when($request->query('kind'), fn ($query, $kind) => $query->where('kind', $kind))
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreLocationRequest $request)
    {
        return new LocationResource(Location::query()->create($request->validated()));
    }

    public function show(Location $location)
    {
        return new LocationResource($location->load('children'));
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return new LocationResource($location);
    }

    public function destroy(Location $location)
    {
        if ($location->children()->exists()) {
            return response()->json([
                'message' => 'This location still contains other locations.',
            ], 409);
        }

        $location->delete();

        return response()->noContent();
    }
}
