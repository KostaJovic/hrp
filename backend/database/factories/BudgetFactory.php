<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory()->expense(),
            'amount_cents' => fake()->numberBetween(5_000, 100_000),
        ];
    }
}
