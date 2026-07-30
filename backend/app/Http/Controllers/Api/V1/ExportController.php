<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Models\MaintenancePlan;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Schema;

class ExportController extends Controller
{
    private const array ENTITIES = [
        'categories' => Category::class,
        'tags' => Tag::class,
        'locations' => Location::class,
        'projects' => Project::class,
        'items' => Item::class,
        'tasks' => Task::class,
        'maintenance_plans' => MaintenancePlan::class,
        'maintenance_logs' => MaintenanceLog::class,
        'expenses' => Expense::class,
        'budgets' => Budget::class,
        'documents' => Document::class,
    ];

    public function json()
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            // Uploaded files live in storage/app and are backed up separately.
            'note' => 'Document rows list file metadata only; back up storage/app for the files themselves.',
        ];

        foreach (self::ENTITIES as $name => $model) {
            $query = $model::query();

            if (in_array($name, ['items', 'projects'], true)) {
                $query->withTrashed();
            }

            if (in_array($name, ['items', 'tasks'], true)) {
                $query->with('tags:id,name');
            }

            $payload[$name] = $query->get();
        }

        return response()
            ->json($payload)
            ->header('Content-Disposition', 'attachment; filename="hrp-backup-'.now()->format('Y-m-d').'.json"');
    }

    public function csv(string $entity)
    {
        abort_unless(array_key_exists($entity, self::ENTITIES), 404);

        $model = self::ENTITIES[$entity];
        $columns = Schema::getColumnListing((new $model)->getTable());
        $taggable = in_array($entity, ['items', 'tasks'], true);

        $query = $model::query();
        if (in_array($entity, ['items', 'projects'], true)) {
            $query->withTrashed();
        }
        if ($taggable) {
            $query->with('tags:id,name');
        }
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows, $columns, $taggable) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens umlauts correctly.
            fwrite($out, "\u{FEFF}");
            fputcsv($out, $taggable ? [...$columns, 'tags'] : $columns);

            foreach ($rows as $row) {
                $values = array_map(
                    fn ($column) => $row->getRawOriginal($column),
                    $columns,
                );
                if ($taggable) {
                    $values[] = $row->tags->pluck('name')->implode(', ');
                }
                fputcsv($out, $values);
            }

            fclose($out);
        }, "hrp-{$entity}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
