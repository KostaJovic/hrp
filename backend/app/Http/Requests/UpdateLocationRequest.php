<?php

namespace App\Http\Requests;

use App\Enums\LocationKind;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLocationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'kind' => ['sometimes', 'required', Rule::enum(LocationKind::class)],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $parentId = $this->input('parent_id');

                if ($parentId === null) {
                    return;
                }

                // Walk up from the requested parent; hitting this location
                // means the move would create a cycle.
                $location = $this->route('location');
                $ancestor = Location::query()->find($parentId);

                while ($ancestor !== null) {
                    if ($ancestor->is($location)) {
                        $validator->errors()->add(
                            'parent_id',
                            'A location cannot be moved under itself or one of its descendants.',
                        );

                        return;
                    }

                    $ancestor = $ancestor->parent;
                }
            },
        ];
    }
}
