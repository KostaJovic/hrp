<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Resolve tag names to models (creating unknown ones) and sync the pivot.
     */
    public function syncTagNames(array $names): void
    {
        $ids = collect($names)
            ->map(fn (string $name) => Tag::query()->firstOrCreate(['name' => $name])->id);

        $this->tags()->sync($ids);
    }
}
