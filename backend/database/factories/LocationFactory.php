<?php

namespace Database\Factories;

use App\Enums\LocationKind;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'kind' => fake()->randomElement(LocationKind::cases()),
            'parent_id' => null,
        ];
    }

    public function kind(LocationKind $kind): static
    {
        return $this->state(['kind' => $kind]);
    }

    public function childOf(Location $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
