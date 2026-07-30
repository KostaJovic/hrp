<?php

namespace App\Http\Requests;

use App\Enums\DocumentKind;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /** Morph aliases that may carry documents. */
    public const array DOCUMENTABLES = ['item', 'expense', 'maintenance_log', 'project'];

    public function rules(): array
    {
        return [
            'documentable_type' => ['required', Rule::in(self::DOCUMENTABLES)],
            'documentable_id' => ['required', 'integer'],
            'kind' => ['required', Rule::enum(DocumentKind::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }

    public function documentable()
    {
        $class = Relation::getMorphedModel($this->input('documentable_type'));

        return $class::query()->find($this->input('documentable_id'));
    }
}
