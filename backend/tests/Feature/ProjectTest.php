<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_creates_project(): void
    {
        $this->postJson('/api/v1/projects', [
            'name' => 'Garage aufräumen',
            'status' => 'active',
            'budget_cents' => 50_000,
        ])->assertCreated()->assertJsonPath('data.status', 'active');
    }

    public function test_ends_on_must_not_precede_starts_on(): void
    {
        $this->postJson('/api/v1/projects', [
            'name' => 'X',
            'status' => 'planned',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-07-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_on');
    }

    public function test_destroy_soft_deletes(): void
    {
        $project = Project::factory()->create();

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertNoContent();

        $this->assertSoftDeleted($project);
        $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(0, 'data');
    }
}
