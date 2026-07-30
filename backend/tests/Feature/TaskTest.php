<?php

namespace Tests\Feature;

use App\Enums\RecurrenceUnit;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_defaults_status_and_priority(): void
    {
        $this->postJson('/api/v1/tasks', ['title' => 'Boiler entkalken', 'tags' => ['Haus']])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'medium')
            ->assertJsonPath('data.tags.0.name', 'Haus');
    }

    public function test_recurrence_fields_must_come_in_pairs(): void
    {
        $this->postJson('/api/v1/tasks', ['title' => 'X', 'recurrence_interval' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('recurrence_unit');
    }

    public function test_completing_a_recurring_task_spawns_next_occurrence(): void
    {
        $task = Task::factory()
            ->recurring(6, RecurrenceUnit::Month)
            ->create(['due_date' => now()->addDays(3)->toDateString()]);
        $task->syncTagNames(['Haus']);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/complete")->assertOk();

        $this->assertSame(TaskStatus::Done, $task->refresh()->status);
        $this->assertNotNull($task->completed_at);

        $nextDue = $response->json('next.due_date');
        $this->assertSame(now()->addDays(3)->addMonthsNoOverflow(6)->toDateString(), $nextDue);
        $this->assertSame(['Haus'], Task::query()->find($response->json('next.id'))->tags->pluck('name')->all());
    }

    public function test_completing_an_overdue_recurring_task_schedules_from_today(): void
    {
        $task = Task::factory()
            ->recurring(1, RecurrenceUnit::Month)
            ->create(['due_date' => now()->subMonths(3)->toDateString()]);

        $response = $this->postJson("/api/v1/tasks/{$task->id}/complete")->assertOk();

        $this->assertSame(
            today()->addMonthsNoOverflow(1)->toDateString(),
            $response->json('next.due_date'),
        );
    }

    public function test_completing_a_one_off_task_spawns_nothing(): void
    {
        $task = Task::factory()->create();

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('next', null);

        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_completing_twice_conflicts(): void
    {
        $task = Task::factory()->create();

        $this->postJson("/api/v1/tasks/{$task->id}/complete")->assertOk();
        $this->postJson("/api/v1/tasks/{$task->id}/complete")->assertStatus(409);
    }

    public function test_filters(): void
    {
        $project = Project::factory()->create();
        Task::factory()->create(['status' => TaskStatus::Done, 'project_id' => $project->id]);
        $urgent = Task::factory()->create(['priority' => 'urgent', 'due_date' => '2026-08-01']);
        $urgent->syncTagNames(['Auto']);

        $this->getJson('/api/v1/tasks?status=done')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/tasks?priority=urgent')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/tasks?project_id={$project->id}")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/tasks?tag=Auto')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/tasks?due_before=2026-08-15')->assertOk()->assertJsonCount(1, 'data');
    }
}
