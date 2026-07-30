<?php

namespace App\Models;

use App\Models\Concerns\HasDocuments;
use Database\Factories\MaintenanceLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_id', 'maintenance_plan_id', 'performed_on', 'cost_cents', 'notes'])]
class MaintenanceLog extends Model
{
    /** @use HasFactory<MaintenanceLogFactory> */
    use HasDocuments, HasFactory;

    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }
}
