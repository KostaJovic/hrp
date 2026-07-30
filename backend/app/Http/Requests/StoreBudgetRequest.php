<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where('type', CategoryType::Expense->value),
                'unique:budgets,category_id',
            ],
            'amount_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
