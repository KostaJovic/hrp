<?php

namespace App\Models;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['kind', 'title', 'file_path', 'original_name', 'mime_type', 'size'])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'kind' => DocumentKind::class,
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
