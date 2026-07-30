<?php

namespace App\Models;

use App\Enums\RecurrenceUnit;
use App\Models\Concerns\HasDocuments;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'description', 'amount_cents', 'category_id', 'spent_on',
    'project_id', 'item_id', 'recurrence_interval', 'recurrence_unit', 'next_due_on',
])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasDocuments, HasFactory;

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'recurrence_unit' => RecurrenceUnit::class,
            'next_due_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
