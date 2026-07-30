<?php

namespace Tests\Feature;

use App\Enums\RecurrenceUnit;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\MaintenancePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_store_plan_for_item(): void
    {
        $item = Item::factory()->create();

        $this->postJson('/api/v1/maintenance-plans', [
            'item_id' => $item->id,
            'name' => 'Boiler entkalken',
            'recurrence_interval' => 6,
            'recurrence_unit' => 'month',
            'next_due_on' => '2026-09-01',
        ])->assertCreated()->assertJsonPath('data.next_due_on', '2026-09-01');
    }

    public function test_logging_against_a_plan_advances_next_due_on(): void
    {
        $plan = MaintenancePlan::factory()->create([
            'recurrence_interval' => 6,
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => '2026-08-01',
        ]);

        $this->postJson('/api/v1/maintenance-logs', [
            'item_id' => $plan->item_id,
            'maintenance_plan_id' => $plan->id,
            'performed_on' => '2026-08-03',
            'cost_cents' => 12_000,
        ])->assertCreated();

        $this->assertSame('2027-02-03', $plan->refresh()->next_due_on->toDateString());
    }

    public function test_log_without_plan_advances_nothing(): void
    {
        $plan = MaintenancePlan::factory()->create(['next_due_on' => '2026-08-01']);

        $this->postJson('/api/v1/maintenance-logs', [
            'item_id' => $plan->item_id,
            'performed_on' => '2026-08-03',
        ])->assertCreated();

        $this->assertSame('2026-08-01', $plan->refresh()->next_due_on->toDateString());
    }

    public function test_log_rejects_plan_of_a_different_item(): void
    {
        $plan = MaintenancePlan::factory()->create();
        $otherItem = Item::factory()->create();

        $this->postJson('/api/v1/maintenance-logs', [
            'item_id' => $otherItem->id,
            'maintenance_plan_id' => $plan->id,
            'performed_on' => '2026-08-03',
        ])->assertUnprocessable()->assertJsonValidationErrors('maintenance_plan_id');
    }

    public function test_deleting_item_cascades_plans_and_logs(): void
    {
        $item = Item::factory()->create();
        MaintenancePlan::factory()->create(['item_id' => $item->id]);
        MaintenanceLog::factory()->create(['item_id' => $item->id]);

        $item->forceDelete();

        $this->assertDatabaseCount('maintenance_plans', 0);
        $this->assertDatabaseCount('maintenance_logs', 0);
    }

    public function test_plans_filterable_by_item_and_due_date(): void
    {
        $plan = MaintenancePlan::factory()->create(['next_due_on' => '2026-08-01']);
        MaintenancePlan::factory()->create(['next_due_on' => '2027-01-01']);

        $this->getJson('/api/v1/maintenance-plans?due_before=2026-09-01')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/maintenance-plans?item_id={$plan->item_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
