<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_upload_photo_to_item_and_download_it(): void
    {
        $item = Item::factory()->create();

        $response = $this->postJson('/api/v1/documents', [
            'documentable_type' => 'item',
            'documentable_id' => $item->id,
            'kind' => 'photo',
            'file' => UploadedFile::fake()->image('bohrmaschine.jpg'),
        ])->assertCreated()->assertJsonPath('data.original_name', 'bohrmaschine.jpg');

        Storage::assertExists($item->documents()->first()->file_path);

        $this->get($response->json('data.download_url'))
            ->assertOk()
            ->assertDownload('bohrmaschine.jpg');
    }

    public function test_index_lists_documents_of_one_record(): void
    {
        $item = Item::factory()->create();
        $other = Item::factory()->create();

        foreach ([$item, $item, $other] as $target) {
            $this->postJson('/api/v1/documents', [
                'documentable_type' => 'item',
                'documentable_id' => $target->id,
                'kind' => 'receipt',
                'file' => UploadedFile::fake()->create('rechnung.pdf', 10, 'application/pdf'),
            ])->assertCreated();
        }

        $this->getJson("/api/v1/documents?documentable_type=item&documentable_id={$item->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_destroy_removes_file_and_row(): void
    {
        $item = Item::factory()->create();

        $id = $this->postJson('/api/v1/documents', [
            'documentable_type' => 'item',
            'documentable_id' => $item->id,
            'kind' => 'manual',
            'file' => UploadedFile::fake()->create('handbuch.pdf', 10, 'application/pdf'),
        ])->json('data.id');

        $path = $item->documents()->first()->file_path;

        $this->deleteJson("/api/v1/documents/{$id}")->assertNoContent();

        Storage::assertMissing($path);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_rejects_unknown_morph_type_and_missing_record(): void
    {
        $this->postJson('/api/v1/documents', [
            'documentable_type' => 'user',
            'documentable_id' => 1,
            'kind' => 'photo',
            'file' => UploadedFile::fake()->image('x.jpg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('documentable_type');

        $this->postJson('/api/v1/documents', [
            'documentable_type' => 'item',
            'documentable_id' => 999,
            'kind' => 'photo',
            'file' => UploadedFile::fake()->image('x.jpg'),
        ])->assertUnprocessable();
    }

    public function test_download_requires_authentication(): void
    {
        $item = Item::factory()->create();

        $response = $this->postJson('/api/v1/documents', [
            'documentable_type' => 'item',
            'documentable_id' => $item->id,
            'kind' => 'invoice',
            'file' => UploadedFile::fake()->create('rechnung.pdf', 10, 'application/pdf'),
        ])->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->getJson($response->json('data.download_url'))->assertUnauthorized();
    }
}
