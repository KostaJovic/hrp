<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/v1/export/json')->assertUnauthorized();
        $this->getJson('/api/v1/export/csv/items')->assertUnauthorized();
    }

    public function test_json_backup_contains_all_entities(): void
    {
        $this->actingAs(User::factory()->create());

        $item = Item::factory()->create(['name' => 'Bohrmaschine']);
        $item->syncTagNames(['werkstatt']);
        Location::factory()->create(['name' => 'Keller']);
        Expense::factory()->create(['description' => 'Wocheneinkauf']);
        $deleted = Item::factory()->create(['name' => 'Weggeworfen']);
        $deleted->delete();

        $response = $this->get('/api/v1/export/json')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="hrp-backup-'.now()->format('Y-m-d').'.json"');

        $payload = $response->json();
        $this->assertSame('Bohrmaschine', $payload['items'][0]['name']);
        $this->assertSame('werkstatt', $payload['items'][0]['tags'][0]['name']);
        $this->assertSame('Keller', $payload['locations'][0]['name']);
        $this->assertSame('Wocheneinkauf', $payload['expenses'][0]['description']);
        // Soft-deleted rows belong in a backup.
        $this->assertCount(2, $payload['items']);
    }

    public function test_csv_export_has_header_and_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $item = Item::factory()->create(['name' => 'Bohrmaschine']);
        $item->syncTagNames(['werkstatt', 'akku']);

        $response = $this->get('/api/v1/export/csv/items')->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $lines = explode("\n", trim($csv));
        $this->assertStringContainsString('name', $lines[0]);
        $this->assertStringContainsString('tags', $lines[0]);
        $this->assertStringContainsString('Bohrmaschine', $lines[1]);
        $this->assertStringContainsString('werkstatt, akku', $lines[1]);
        // BOM for Excel.
        $this->assertStringStartsWith("\u{FEFF}", $csv);
    }

    public function test_csv_rejects_unknown_entity_and_handles_empty_tables(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/api/v1/export/csv/users')->assertNotFound();

        $empty = $this->get('/api/v1/export/csv/locations')->assertOk();
        $this->assertStringContainsString('name', $empty->streamedContent());
    }
}
