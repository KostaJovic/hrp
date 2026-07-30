<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_expense_with_links(): void
    {
        $category = Category::factory()->expense()->create();
        $project = Project::factory()->create();
        $item = Item::factory()->create();

        $this->postJson('/api/v1/expenses', [
            'description' => 'Neuer Akkuschrauber',
            'amount_cents' => 12_999,
            'category_id' => $category->id,
            'spent_on' => '2026-07-15',
            'project_id' => $project->id,
            'item_id' => $item->id,
        ])->assertCreated()->assertJsonPath('data.amount_cents', 12999);
    }

    public function test_item_type_category_is_rejected(): void
    {
        $itemCategory = Category::factory()->create(); // type: item

        $this->postJson('/api/v1/expenses', [
            'description' => 'X',
            'amount_cents' => 100,
            'category_id' => $itemCategory->id,
            'spent_on' => '2026-07-15',
        ])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_recurring_expense_stores_schedule_columns(): void
    {
        $this->postJson('/api/v1/expenses', [
            'description' => 'Netflix',
            'amount_cents' => 1_499,
            'spent_on' => '2026-07-01',
            'recurrence_interval' => 1,
            'recurrence_unit' => 'month',
            'next_due_on' => '2026-08-01',
        ])->assertCreated()->assertJsonPath('data.next_due_on', '2026-08-01');
    }

    public function test_recurring_filter(): void
    {
        Expense::factory()->create();
        Expense::factory()->create([
            'recurrence_interval' => 1,
            'recurrence_unit' => 'month',
            'next_due_on' => '2026-08-01',
        ]);

        $this->getJson('/api/v1/expenses?recurring=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_date_range_filter(): void
    {
        Expense::factory()->create(['spent_on' => '2026-05-01']);
        Expense::factory()->create(['spent_on' => '2026-06-15']);
        Expense::factory()->create(['spent_on' => '2026-07-20']);

        $this->getJson('/api/v1/expenses?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
