<?php

namespace App\Http\Requests\CarePlan;

use App\Enums\Auth\RoleEnum;
use App\Http\Requests\BaseRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Validation\Rule;

class CarePlanRangeDateRequest extends BaseRequest
{
    /**
     * rulesGet
     * handle rule method get
     *
     * @return array
     */
    public function rulesGet(): array
    {
        $branchId = request()->input('branch_id', auth()->user()->branch_id);
        return [
            'branch_id' => [
                'sometimes',
                'integer',
                Rule::exists(Branch::class, 'id')
                    ->where('facility_id', auth()->user()->facility_id)
                    ->whereNull('deleted_at')
            ],
            'start_date' => 'required|string|date_format:Y-m-d',
            'end_date' => 'required|string|date_format:Y-m-d,after:start_date',
            'patient_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($branchId) {
                    $checkUserExists = User::where('id', $value)
                        ->where('branch_id', $branchId)
                        ->whereHas('role', function ($query) {
                            $query->where('name', RoleEnum::USER);
                        })
                        ->exists();

                    if (!$checkUserExists) {
                        $fail(__('validation.exists', ['attribute' => $attribute]));
                    }
                }
            ],
        ];
    }

    /**
     * Custom message for rule
     *
     * @return array
     */
    public function getMessages(): array
    {
        return [];
    }
}
