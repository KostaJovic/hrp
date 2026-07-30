<?php

namespace Tests\Feature;

use App\Enums\LocationKind;
use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_creates_item_with_tags(): void
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        $this->postJson('/api/v1/items', [
            'name' => 'Canon RF 50mm',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'purchase_price_cents' => 25_000,
            'warranty_until' => '2027-01-01',
            'tags' => ['kamera', 'objektiv'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Canon RF 50mm')
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseCount('tags', 2);
    }

    public function test_update_syncs_tags_without_duplicating(): void
    {
        $item = Item::factory()->create();
        $item->syncTagNames(['kamera', 'objektiv']);

        $this->patchJson("/api/v1/items/{$item->id}", ['tags' => ['kamera', 'stativ']])
            ->assertOk()
            ->assertJsonCount(2, 'data.tags');

        $this->assertEqualsCanonicalizing(
            ['kamera', 'stativ'],
            $item->refresh()->tags->pluck('name')->all(),
        );
        // "objektiv" still exists as a tag, it is just detached.
        $this->assertDatabaseCount('tags', 3);
    }

    public function test_search_matches_name_description_serial_and_notes(): void
    {
        Item::factory()->create(['name' => 'Bohrmaschine']);
        Item::factory()->create(['description' => 'Ersatzteil für Bohrmaschine']);
        Item::factory()->create(['serial_number' => 'BOHR-123']);
        Item::factory()->create(['name' => 'Kaffeemaschine']);

        $this->getJson('/api/v1/items?q=bohr')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_location_filter_includes_descendants(): void
    {
        $basement = Location::factory()->kind(LocationKind::Room)->create(['name' => 'Keller']);
        $shelf = Location::factory()->kind(LocationKind::Shelf)->childOf($basement)->create();
        $garage = Location::factory()->kind(LocationKind::Garage)->create();

        Item::factory()->create(['location_id' => $basement->id]);
        Item::factory()->create(['location_id' => $shelf->id]);
        Item::factory()->create(['location_id' => $garage->id]);

        $this->getJson("/api/v1/items?location_id={$basement->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_warranty_active_filter(): void
    {
        Item::factory()->create(['warranty_until' => now()->addYear()->toDateString()]);
        Item::factory()->create(['warranty_until' => now()->subDay()->toDateString()]);
        Item::factory()->create(['warranty_until' => null]);

        $this->getJson('/api/v1/items?warranty=active')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_tag_and_category_filters(): void
    {
        $lenses = Category::factory()->create(['name' => 'Objektive']);
        $tagged = Item::factory()->create(['category_id' => $lenses->id]);
        $tagged->syncTagNames(['kamera']);
        Item::factory()->create();

        $this->getJson('/api/v1/items?tag=kamera')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/items?category_id={$lenses->id}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_project_filter(): void
    {
        $project = Project::factory()->create();
        Item::factory()->create(['project_id' => $project->id]);
        Item::factory()->create();

        $this->getJson("/api/v1/items?project_id={$project->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_pagination_and_sorting(): void
    {
        Item::factory()->create(['name' => 'Alpha']);
        Item::factory()->create(['name' => 'Zulu']);
        Item::factory()->create(['name' => 'Mike']);

        $response = $this->getJson('/api/v1/items?sort=name&per_page=2')->assertOk();

        $response->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('meta.total', 3);

        $this->getJson('/api/v1/items?sort=bogus')->assertUnprocessable();
    }

    public function test_destroy_soft_deletes_and_detaches_nothing(): void
    {
        $item = Item::factory()->create();
        $item->syncTagNames(['kamera']);

        $this->deleteJson("/api/v1/items/{$item->id}")->assertNoContent();

        $this->assertSoftDeleted($item);
        $this->assertDatabaseHas('tags', ['name' => 'kamera']);
    }

    public function test_deleting_referenced_category_nulls_the_item_reference(): void
    {
        $category = Category::factory()->create();
        $item = Item::factory()->create(['category_id' => $category->id]);

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertNoContent();

        $this->assertNull($item->refresh()->category_id);
    }
}
