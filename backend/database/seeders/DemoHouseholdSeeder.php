<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\LocationKind;
use App\Enums\ProjectStatus;
use App\Enums\RecurrenceUnit;
use App\Enums\TaskPriority;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Models\MaintenancePlan;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class DemoHouseholdSeeder extends Seeder
{
    public function run(): void
    {
        // Locations: Keller → Regal A → Box 1, plus Garage, Büro, Auto.
        $keller = Location::query()->create(['name' => 'Keller', 'kind' => LocationKind::Room]);
        $regalA = Location::query()->create(['name' => 'Regal A', 'kind' => LocationKind::Shelf, 'parent_id' => $keller->id]);
        $box1 = Location::query()->create(['name' => 'Box 1 (Elektro)', 'kind' => LocationKind::Box, 'parent_id' => $regalA->id]);
        $garage = Location::query()->create(['name' => 'Garage', 'kind' => LocationKind::Garage]);
        $buero = Location::query()->create(['name' => 'Büro', 'kind' => LocationKind::Room]);
        $auto = Location::query()->create(['name' => 'Auto', 'kind' => LocationKind::Vehicle]);

        // Categories for both worlds.
        $itemCats = collect(['Werkzeuge', 'Elektronik', 'Kamera', 'Haushalt', 'Ersatzteile'])
            ->mapWithKeys(fn ($name) => [$name => Category::query()->create(['name' => $name, 'type' => CategoryType::Item])]);
        $expenseCats = collect(['Lebensmittel', 'Auto', 'Software', 'Haushalt', 'Werkzeuge'])
            ->mapWithKeys(fn ($name) => [$name => Category::query()->create(['name' => $name, 'type' => CategoryType::Expense])]);

        $projekt = Project::query()->create([
            'name' => 'Kellerausbau',
            'status' => ProjectStatus::Active,
            'budget_cents' => 250_000,
            'description' => 'Regale, Beleuchtung und Ordnungssystem für den Keller.',
        ]);

        // A recognisable household inventory.
        $inventory = [
            ['Bohrmaschine Bosch GSB 18V', 'Werkzeuge', $garage, 18_999, '2025-03-12', '2027-03-12', ['akku', 'werkstatt']],
            ['Canon EOS R6', 'Kamera', $buero, 249_900, '2024-11-02', '2026-11-02', ['kamera']],
            ['Canon RF 50mm f/1.8', 'Kamera', $buero, 21_900, '2024-12-24', '2026-12-24', ['kamera', 'objektiv']],
            ['Canon RF 24-105mm f/4', 'Kamera', $buero, 119_000, '2025-06-01', '2027-06-01', ['kamera', 'objektiv']],
            ['Werkzeugkoffer', 'Werkzeuge', $garage, 8_990, '2023-05-20', null, ['werkstatt']],
            ['Ersatzsicherungen 16A', 'Ersatzteile', $box1, 599, null, null, ['ersatzteil', 'elektro']],
            ['Verlängerungskabel 10m', 'Elektronik', $box1, 1_499, null, null, ['elektro']],
            ['Staubsauger Miele', 'Haushalt', $keller, 29_900, '2024-08-15', '2026-08-15', []],
            ['Winterreifen Satz', 'Ersatzteile', $garage, 45_000, '2024-10-05', null, ['auto']],
            ['Campingzelt 4P', 'Haushalt', $regalA, 19_900, '2023-07-01', null, ['camping']],
        ];

        foreach ($inventory as [$name, $cat, $location, $price, $purchased, $warranty, $tags]) {
            $item = Item::query()->create([
                'name' => $name,
                'category_id' => $itemCats[$cat]->id,
                'location_id' => $location->id,
                'purchase_price_cents' => $price,
                'purchased_at' => $purchased,
                'warranty_until' => $warranty,
            ]);
            $item->syncTagNames($tags);
        }

        // Filler items so lists and pagination have something to chew on.
        Item::factory()->count(20)->create([
            'category_id' => $itemCats['Haushalt']->id,
            'location_id' => $keller->id,
        ]);

        $bohrmaschine = Item::query()->firstWhere('name', 'like', 'Bohrmaschine%');
        $autoItem = Item::query()->create([
            'name' => 'PKW Skoda Octavia',
            'category_id' => null,
            'location_id' => $garage->id,
            'purchased_at' => '2022-04-01',
        ]);

        // Maintenance: a serviced car and a due boiler check.
        $service = MaintenancePlan::query()->create([
            'item_id' => $autoItem->id,
            'name' => 'Jahresservice',
            'recurrence_interval' => 12,
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => now()->addMonths(4)->toDateString(),
        ]);
        MaintenanceLog::query()->create([
            'item_id' => $autoItem->id,
            'maintenance_plan_id' => $service->id,
            'performed_on' => now()->subMonths(8)->toDateString(),
            'cost_cents' => 38_500,
            'notes' => 'Ölwechsel, Bremsen geprüft.',
        ]);
        MaintenancePlan::query()->create([
            'item_id' => $bohrmaschine->id,
            'name' => 'Akku-Pflege',
            'recurrence_interval' => 6,
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => now()->addDays(10)->toDateString(),
        ]);

        // Tasks, one recurring.
        $t1 = Task::query()->create([
            'title' => 'Rauchmelder testen',
            'priority' => TaskPriority::High,
            'due_date' => now()->addDays(7)->toDateString(),
            'recurrence_interval' => 6,
            'recurrence_unit' => RecurrenceUnit::Month,
        ]);
        $t1->syncTagNames(['Haus']);
        $t2 = Task::query()->create([
            'title' => 'Kellerregal montieren',
            'priority' => TaskPriority::Medium,
            'project_id' => $projekt->id,
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
        $t2->syncTagNames(['Haus', 'Projekt']);

        // Expenses incl. a subscription.
        Expense::query()->create([
            'description' => 'Wocheneinkauf',
            'amount_cents' => 8_734,
            'category_id' => $expenseCats['Lebensmittel']->id,
            'spent_on' => now()->subDays(3)->toDateString(),
        ]);
        Expense::query()->create([
            'description' => 'Regalbretter Baumarkt',
            'amount_cents' => 6_450,
            'category_id' => $expenseCats['Werkzeuge']->id,
            'project_id' => $projekt->id,
            'spent_on' => now()->subDays(10)->toDateString(),
        ]);
        Expense::query()->create([
            'description' => 'Adobe Lightroom Abo',
            'amount_cents' => 1_190,
            'category_id' => $expenseCats['Software']->id,
            'spent_on' => now()->startOfMonth()->toDateString(),
            'recurrence_interval' => 1,
            'recurrence_unit' => RecurrenceUnit::Month,
            'next_due_on' => now()->startOfMonth()->addMonthsNoOverflow(1)->toDateString(),
        ]);
    }
}
