<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\MaintenanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'maintenance_plan_id' => null,
            'performed_on' => fake()->dateTimeBetween('-1 year'),
            'cost_cents' => fake()->optional()->numberBetween(1_000, 100_000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
