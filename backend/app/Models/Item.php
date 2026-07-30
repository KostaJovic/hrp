<?php

namespace App\Models;

use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasTags;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'description', 'notes', 'serial_number', 'quantity',
    'category_id', 'location_id', 'project_id',
    'purchase_price_cents', 'current_value_cents', 'purchased_at', 'warranty_until',
])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasDocuments, HasFactory, HasTags, SoftDeletes;

    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'warranty_until' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
