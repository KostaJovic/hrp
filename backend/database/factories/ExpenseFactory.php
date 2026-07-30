<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount_cents' => fake()->numberBetween(100, 200_000),
            'category_id' => null,
            'spent_on' => fake()->dateTimeBetween('-6 months'),
            'project_id' => null,
            'item_id' => null,
            'recurrence_interval' => null,
            'recurrence_unit' => null,
            'next_due_on' => null,
        ];
    }
}
