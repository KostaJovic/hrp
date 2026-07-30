<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $category = $this->route('category');
        $type = $this->input('type', $category->type->value);

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('categories')->where('type', $type)->ignore($category),
            ],
            'type' => ['sometimes', 'required', Rule::enum(CategoryType::class)],
        ];
    }
}
