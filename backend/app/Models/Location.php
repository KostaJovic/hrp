<?php

namespace App\Models;

use App\Enums\LocationKind;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['name', 'kind', 'parent_id', 'description'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'kind' => LocationKind::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * IDs of this location and every location nested below it.
     *
     * ponytail: in-memory tree walk over all locations — fine for a household,
     * switch to a recursive CTE if the table ever grows past a few thousand rows.
     */
    public function subtreeIds(): Collection
    {
        $byParent = self::query()->get(['id', 'parent_id'])->groupBy('parent_id');

        $ids = collect([$this->id]);
        $queue = [$this->id];

        while ($queue !== []) {
            $children = $byParent->get(array_shift($queue), collect());

            foreach ($children as $child) {
                $ids->push($child->id);
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
