<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['month' => ['sometimes', 'date_format:Y-m']]);
        $start = Carbon::createFromFormat('Y-m', $validated['month'] ?? now()->format('Y-m'))->startOfMonth();

        $spent = Expense::query()
            ->whereBetween('spent_on', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, sum(amount_cents) as total')
            ->pluck('total', 'category_id');

        $budgets = Budget::query()->with('category')->get()
            ->sortBy(fn (Budget $budget) => $budget->category->name)
            ->values()
            ->each(fn (Budget $budget) => $budget->spent_cents = (int) ($spent[$budget->category_id] ?? 0));

        return BudgetResource::collection($budgets);
    }

    public function store(StoreBudgetRequest $request)
    {
        return new BudgetResource(Budget::query()->create($request->validated())->load('category'));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget)
    {
        $budget->update($request->validated());

        return new BudgetResource($budget->load('category'));
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return response()->noContent();
    }
}
