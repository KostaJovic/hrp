<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use App\Enums\RecurrenceUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::Expense->value),
            ],
            'spent_on' => ['required', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'required_with:recurrence_unit'],
            'recurrence_unit' => ['nullable', Rule::enum(RecurrenceUnit::class), 'required_with:recurrence_interval'],
            'next_due_on' => ['nullable', 'date'],
        ];
    }
}
