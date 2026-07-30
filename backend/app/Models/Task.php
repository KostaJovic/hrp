<?php

namespace App\Models;

use App\Enums\RecurrenceUnit;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasTags;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title', 'description', 'status', 'priority', 'due_date',
    'project_id', 'recurrence_interval', 'recurrence_unit',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, HasTags;

    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'recurrence_unit' => RecurrenceUnit::class,
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_interval !== null && $this->recurrence_unit !== null;
    }

    /**
     * Mark this task done and, if recurring, create the next occurrence.
     * A new row (instead of shifting the due date) keeps the history intact.
     */
    public function complete(): ?self
    {
        $this->forceFill(['status' => TaskStatus::Done, 'completed_at' => now()])->save();

        if (! $this->isRecurring()) {
            return null;
        }

        // Base the next occurrence on the schedule when on time, on today
        // when completing an overdue task (avoids spawning already-due work).
        $base = $this->due_date === null || $this->due_date->isPast()
            ? today()
            : $this->due_date;

        $next = self::query()->create([
            'title' => $this->title,
            'description' => $this->description,
            'status' => TaskStatus::Open,
            'priority' => $this->priority,
            'due_date' => $this->recurrence_unit->addTo($base->copy(), $this->recurrence_interval),
            'project_id' => $this->project_id,
            'recurrence_interval' => $this->recurrence_interval,
            'recurrence_unit' => $this->recurrence_unit,
        ]);

        $next->tags()->sync($this->tags()->pluck('tags.id'));

        return $next;
    }
}
