<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'item_id' => ['sometimes', 'integer', 'exists:items,id'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $expenses = Expense::query()
            ->with('category')
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['project_id'] ?? null, fn ($query, $id) => $query->where('project_id', $id))
            ->when($filters['item_id'] ?? null, fn ($query, $id) => $query->where('item_id', $id))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('spent_on', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('spent_on', '<=', $date))
            ->orderByDesc('spent_on')
            ->paginate($filters['per_page'] ?? 25);

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request)
    {
        return new ExpenseResource(Expense::query()->create($request->validated()));
    }

    public function show(Expense $expense)
    {
        return new ExpenseResource($expense->load(['category', 'project', 'item']));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());

        return new ExpenseResource($expense);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->noContent();
    }
}
