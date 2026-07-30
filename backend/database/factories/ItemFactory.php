<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'notes' => null,
            'serial_number' => fake()->optional()->bothify('SN-####-????'),
            'quantity' => 1,
            'category_id' => null,
            'location_id' => null,
            'project_id' => null,
            'purchase_price_cents' => fake()->optional()->numberBetween(500, 500_000),
            'current_value_cents' => null,
            'purchased_at' => fake()->optional()->date(),
            'warranty_until' => null,
        ];
    }
}
