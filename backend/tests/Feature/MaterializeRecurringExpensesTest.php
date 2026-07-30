<?php

namespace Tests\Feature;

use App\Enums\RecurrenceUnit;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterializeRecurringExpensesTest extends TestCase
{
    use RefreshDatabase;

    private function subscription(array $attributes = []): Expense
    {
        return Expense::factory()->create(array_merge([
            'description' => 'Netflix',
            'amount_cents' => 1_499,
            'recurrence_interval' => 1,
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => today()->toDateString(),
        ], $attributes));
    }

    public function test_books_due_subscription_and_advances_schedule(): void
    {
        $template = $this->subscription();

        $this->artisan('expenses:materialize')->assertSuccessful();

        $booked = Expense::query()->whereNull('recurrence_interval')->where('description', 'Netflix')->first();
        $this->assertNotNull($booked);
        $this->assertSame(today()->toDateString(), $booked->spent_on->toDateString());
        $this->assertSame(1499, $booked->amount_cents);

        $this->assertSame(
            today()->addMonthsNoOverflow(1)->toDateString(),
            $template->refresh()->next_due_on->toDateString(),
        );
    }

    public function test_catches_up_missed_periods(): void
    {
        $this->subscription(['next_due_on' => today()->subMonths(2)->toDateString()]);

        $this->artisan('expenses:materialize')->assertSuccessful();

        // Two missed months plus the current due date.
        $this->assertSame(3, Expense::query()->whereNull('recurrence_interval')->count());
    }

    public function test_is_idempotent_and_ignores_future_and_plain_expenses(): void
    {
        $this->subscription(['next_due_on' => today()->addDays(5)->toDateString()]);
        Expense::factory()->create();

        $this->artisan('expenses:materialize')->assertSuccessful();
        $this->artisan('expenses:materialize')->assertSuccessful();

        // Only the two originals — nothing was due.
        $this->assertDatabaseCount('expenses', 2);
    }
}
