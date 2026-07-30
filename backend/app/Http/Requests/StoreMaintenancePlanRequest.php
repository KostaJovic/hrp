<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenancePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'recurrence_interval' => ['required', 'integer', 'min:1'],
            'recurrence_unit' => ['required', Rule::enum(RecurrenceUnit::class)],
            'next_due_on' => ['required', 'date'],
        ];
    }
}
