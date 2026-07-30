<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_crud_roundtrip(): void
    {
        $this->postJson('/api/v1/tags', ['name' => 'kamera'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'kamera');

        $tag = Tag::query()->firstWhere('name', 'kamera');

        $this->getJson('/api/v1/tags')->assertOk()->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/tags/{$tag->id}", ['name' => 'fotografie'])
            ->assertOk()
            ->assertJsonPath('data.name', 'fotografie');

        $this->deleteJson("/api/v1/tags/{$tag->id}")->assertNoContent();
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_duplicate_names_are_rejected(): void
    {
        Tag::factory()->create(['name' => 'kamera']);

        $this->postJson('/api/v1/tags', ['name' => 'kamera'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
