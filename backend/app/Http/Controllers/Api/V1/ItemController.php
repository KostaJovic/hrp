<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'location_id' => ['sometimes', 'integer', 'exists:locations,id'],
            'tag' => ['sometimes', 'string', 'max:255'],
            'warranty' => ['sometimes', Rule::in(['active'])],
            'sort' => ['sometimes', Rule::in([
                'name', '-name', 'created_at', '-created_at',
                'purchased_at', '-purchased_at', 'current_value_cents', '-current_value_cents',
            ])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $sort = $filters['sort'] ?? '-created_at';
        [$column, $direction] = str_starts_with($sort, '-')
            ? [substr($sort, 1), 'desc']
            : [$sort, 'asc'];

        $items = Item::query()
            ->with(['category', 'location', 'tags'])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $like = '%'.$q.'%';
                $query->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('serial_number', 'like', $like)
                    ->orWhere('notes', 'like', $like));
            })
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['location_id'] ?? null, fn ($query, $id) => $query->whereIn(
                'location_id',
                Location::query()->findOrFail($id)->subtreeIds(),
            ))
            ->when($filters['tag'] ?? null, fn ($query, $tag) => $query->whereHas(
                'tags', fn ($w) => $w->where('name', $tag),
            ))
            ->when(($filters['warranty'] ?? null) === 'active', fn ($query) => $query
                ->whereDate('warranty_until', '>=', today()))
            ->orderBy($column, $direction)
            ->paginate($filters['per_page'] ?? 25);

        return ItemResource::collection($items);
    }

    public function store(StoreItemRequest $request)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $item = Item::query()->create($data);

        if ($tags !== null) {
            $item->syncTagNames($tags);
        }

        return new ItemResource($item->load(['category', 'location', 'tags']));
    }

    public function show(Item $item)
    {
        return new ItemResource($item->load(['category', 'location', 'project', 'tags']));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $item->update($data);

        if ($tags !== null) {
            $item->syncTagNames($tags);
        }

        return new ItemResource($item->load(['category', 'location', 'tags']));
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return response()->noContent();
    }
}
