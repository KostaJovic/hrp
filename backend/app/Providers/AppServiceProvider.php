<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\Item;
use App\Models\MaintenanceLog;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stable short names in polymorphic columns instead of PHP class
        // names — survives refactors and keeps exports (HOM-13) readable.
        Relation::enforceMorphMap([
            'item' => Item::class,
            'task' => Task::class,
            'project' => Project::class,
            'expense' => Expense::class,
            'maintenance_log' => MaintenanceLog::class,
        ]);
    }
}
