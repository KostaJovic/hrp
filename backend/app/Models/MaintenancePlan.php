<?php

namespace App\Models;

use App\Enums\RecurrenceUnit;
use Carbon\CarbonInterface;
use Database\Factories\MaintenancePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['item_id', 'name', 'notes', 'recurrence_interval', 'recurrence_unit', 'next_due_on'])]
class MaintenancePlan extends Model
{
    /** @use HasFactory<MaintenancePlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'recurrence_unit' => RecurrenceUnit::class,
            'next_due_on' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /**
     * Advance the schedule after work was performed.
     */
    public function advanceFrom(CarbonInterface $performedOn): void
    {
        $this->update([
            'next_due_on' => $this->recurrence_unit->addTo($performedOn->copy(), $this->recurrence_interval),
        ]);
    }
}
