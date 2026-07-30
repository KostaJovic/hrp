<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => CategoryType::Item,
        ];
    }

    public function expense(): static
    {
        return $this->state(['type' => CategoryType::Expense]);
    }
}
