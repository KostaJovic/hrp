<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function rules(): array
    {
        // The category is the budget's identity — change amounts, not categories.
        return [
            'amount_cents' => ['required', 'integer', 'min:0'],
        ];
    }
}
