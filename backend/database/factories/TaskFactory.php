<?php

namespace Database\Factories;

use App\Enums\RecurrenceUnit;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Medium,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
            'project_id' => null,
            'recurrence_interval' => null,
            'recurrence_unit' => null,
        ];
    }

    public function recurring(int $interval, RecurrenceUnit $unit): static
    {
        return $this->state([
            'recurrence_interval' => $interval,
            'recurrence_unit' => $unit,
        ]);
    }
}
