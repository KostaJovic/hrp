<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\Concerns\HasDocuments;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'status', 'starts_on', 'ends_on', 'budget_cents', 'notes'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasDocuments, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
