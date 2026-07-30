<?php

namespace Database\Factories;

use App\Enums\RecurrenceUnit;
use App\Models\Item;
use App\Models\MaintenancePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenancePlan>
 */
class MaintenancePlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'name' => fake()->words(3, true),
            'notes' => null,
            'recurrence_interval' => fake()->randomElement([1, 3, 6, 12]),
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => fake()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
