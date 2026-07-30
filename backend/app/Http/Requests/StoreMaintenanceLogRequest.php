<?php

namespace App\Http\Requests;

use App\Models\MaintenancePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMaintenanceLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'maintenance_plan_id' => ['nullable', 'integer', 'exists:maintenance_plans,id'],
            'performed_on' => ['required', 'date'],
            'cost_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $planId = $this->input('maintenance_plan_id');

                if ($planId === null) {
                    return;
                }

                $plan = MaintenancePlan::query()->find($planId);

                if ($plan !== null && (int) $this->input('item_id') !== $plan->item_id) {
                    $validator->errors()->add(
                        'maintenance_plan_id',
                        'The maintenance plan belongs to a different item.',
                    );
                }
            },
        ];
    }
}
