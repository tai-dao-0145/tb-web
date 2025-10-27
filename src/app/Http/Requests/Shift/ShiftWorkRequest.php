<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\BaseRequest;
use App\Rules\ValidShiftUserRule;
use Illuminate\Validation\Rule;

class ShiftWorkRequest extends BaseRequest
{
    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        $workDate = request()->input('work_date');
        $branchId = request()->input('branch_id');
        $shiftId = request()->input('shift_id');
        return [
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
            'user_id' => [
                'bail',
                'required',
                'integer',
                'exists:users,id,deleted_at,NULL',
                Rule::exists('users', 'id')
                    ->whereNull('deleted_at')
                    ->where('branch_id', $branchId),
                new ValidShiftUserRule($workDate, $branchId, shiftId: $shiftId, isRequest: true),
            ],
            'shift_type_id' => [
                'bail',
                'required',
                'integer',
                'exists:shift_types,id,deleted_at,NULL',
                Rule::exists('shift_types', 'id')
                    ->whereNull('deleted_at')
                    ->where('branch_id', $branchId),
            ],
            'work_date' => 'required|date_format:Y-m-d',
            'shift_id' => [
                'bail',
                'nullable',
                'integer',
                'exists:shifts,id,deleted_at,NULL',
                Rule::exists('shifts', 'id')
                    ->whereNull('deleted_at')
                    ->where('branch_id', $branchId)
                    ->where('user_id', request()->input('user_id'))
                    ->where('work_date', $workDate),
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
