<?php

namespace App\Http\Requests;

use App\Enums\LocationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(LocationKind::class)],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
