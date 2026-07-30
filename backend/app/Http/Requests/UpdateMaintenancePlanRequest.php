<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenancePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_id' => ['sometimes', 'required', 'integer', 'exists:items,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'recurrence_interval' => ['sometimes', 'required', 'integer', 'min:1'],
            'recurrence_unit' => ['sometimes', 'required', Rule::enum(RecurrenceUnit::class)],
            'next_due_on' => ['sometimes', 'required', 'date'],
        ];
    }
}
