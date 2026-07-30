<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['type' => ['sometimes', Rule::enum(CategoryType::class)]]);

        return CategoryResource::collection(
            Category::query()
                ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
                ->orderBy('name')
                ->get(),
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        return new CategoryResource(Category::query()->create($request->validated()));
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->noContent();
    }
}
