<?php

namespace Tests\Feature;

use App\Enums\LocationKind;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_creates_nested_hierarchy(): void
    {
        $room = $this->postJson('/api/v1/locations', ['name' => 'Keller', 'kind' => 'room'])
            ->assertCreated()
            ->json('data');

        $shelf = $this->postJson('/api/v1/locations', [
            'name' => 'Regal A',
            'kind' => 'shelf',
            'parent_id' => $room['id'],
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/locations', [
            'name' => 'Box 1',
            'kind' => 'box',
            'parent_id' => $shelf['id'],
        ])->assertCreated();

        $this->assertDatabaseCount('locations', 3);
    }

    public function test_store_rejects_unknown_parent_and_invalid_kind(): void
    {
        $this->postJson('/api/v1/locations', ['name' => 'X', 'kind' => 'room', 'parent_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->postJson('/api/v1/locations', ['name' => 'X', 'kind' => 'spaceship'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kind');
    }

    public function test_location_cannot_become_its_own_descendant(): void
    {
        $room = Location::factory()->kind(LocationKind::Room)->create();
        $shelf = Location::factory()->kind(LocationKind::Shelf)->childOf($room)->create();

        $this->patchJson("/api/v1/locations/{$room->id}", ['parent_id' => $shelf->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->patchJson("/api/v1/locations/{$room->id}", ['parent_id' => $room->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_destroy_is_blocked_while_children_exist(): void
    {
        $room = Location::factory()->kind(LocationKind::Room)->create();
        $shelf = Location::factory()->kind(LocationKind::Shelf)->childOf($room)->create();

        $this->deleteJson("/api/v1/locations/{$room->id}")->assertStatus(409);

        $this->deleteJson("/api/v1/locations/{$shelf->id}")->assertNoContent();
        $this->deleteJson("/api/v1/locations/{$room->id}")->assertNoContent();
        $this->assertDatabaseCount('locations', 0);
    }

    public function test_subtree_ids_walks_the_hierarchy(): void
    {
        $room = Location::factory()->kind(LocationKind::Room)->create();
        $shelf = Location::factory()->kind(LocationKind::Shelf)->childOf($room)->create();
        $box = Location::factory()->kind(LocationKind::Box)->childOf($shelf)->create();
        $other = Location::factory()->kind(LocationKind::Room)->create();

        $ids = $room->subtreeIds();

        $this->assertEqualsCanonicalizing([$room->id, $shelf->id, $box->id], $ids->all());
        $this->assertNotContains($other->id, $ids);
    }
}
