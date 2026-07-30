<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_index_requires_authentication(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->getJson('/api/v1/categories')->assertUnauthorized();
    }

    public function test_index_lists_categories_filtered_by_type(): void
    {
        Category::factory()->count(2)->create();
        Category::factory()->expense()->create(['name' => 'Lebensmittel']);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(3, 'data');

        $this->getJson('/api/v1/categories?type=expense')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Lebensmittel');
    }

    public function test_store_creates_category(): void
    {
        $this->postJson('/api/v1/categories', ['name' => 'Werkzeuge', 'type' => 'item'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Werkzeuge');

        $this->assertDatabaseHas('categories', ['name' => 'Werkzeuge', 'type' => 'item']);
    }

    public function test_store_rejects_duplicate_name_within_same_type(): void
    {
        Category::factory()->create(['name' => 'Werkzeuge']);

        $this->postJson('/api/v1/categories', ['name' => 'Werkzeuge', 'type' => 'item'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        // Same name is fine for the other type.
        $this->postJson('/api/v1/categories', ['name' => 'Werkzeuge', 'type' => 'expense'])
            ->assertCreated();
    }

    public function test_update_and_destroy(): void
    {
        $category = Category::factory()->create();

        $this->patchJson("/api/v1/categories/{$category->id}", ['name' => 'Elektronik'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Elektronik');

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
