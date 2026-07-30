<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_requires_expense_category_and_uniqueness(): void
    {
        $itemCategory = Category::factory()->create();
        $expenseCategory = Category::factory()->expense()->create();

        $this->postJson('/api/v1/budgets', ['category_id' => $itemCategory->id, 'amount_cents' => 10_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        $this->postJson('/api/v1/budgets', ['category_id' => $expenseCategory->id, 'amount_cents' => 10_000])
            ->assertCreated();

        $this->postJson('/api/v1/budgets', ['category_id' => $expenseCategory->id, 'amount_cents' => 20_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }

    public function test_index_reports_monthly_spent_per_category(): void
    {
        $food = Category::factory()->expense()->create(['name' => 'Lebensmittel']);
        $software = Category::factory()->expense()->create(['name' => 'Software']);
        Budget::factory()->create(['category_id' => $food->id, 'amount_cents' => 40_000]);
        Budget::factory()->create(['category_id' => $software->id, 'amount_cents' => 5_000]);

        Expense::factory()->create(['category_id' => $food->id, 'amount_cents' => 8_734, 'spent_on' => '2026-07-05']);
        Expense::factory()->create(['category_id' => $food->id, 'amount_cents' => 6_266, 'spent_on' => '2026-07-20']);
        // Other month and other category must not count.
        Expense::factory()->create(['category_id' => $food->id, 'amount_cents' => 99_999, 'spent_on' => '2026-06-30']);
        Expense::factory()->create(['category_id' => $software->id, 'amount_cents' => 1_190, 'spent_on' => '2026-07-01']);

        $response = $this->getJson('/api/v1/budgets?month=2026-07')->assertOk();

        $byCategory = collect($response->json('data'))->keyBy('category.name');
        $this->assertSame(15_000, $byCategory['Lebensmittel']['spent_cents']);
        $this->assertSame(1_190, $byCategory['Software']['spent_cents']);
    }

    public function test_update_and_destroy(): void
    {
        $budget = Budget::factory()->create(['amount_cents' => 10_000]);

        $this->patchJson("/api/v1/budgets/{$budget->id}", ['amount_cents' => 25_000])
            ->assertOk()
            ->assertJsonPath('data.amount_cents', 25000);

        $this->deleteJson("/api/v1/budgets/{$budget->id}")->assertNoContent();
        $this->assertDatabaseCount('budgets', 0);
    }
}
