<?php

namespace App\Http\Requests\Shift;

use App\Enums\Auth\RoleEnum;
use App\Enums\Shift\ShiftStatusEnum;
use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use App\Rules\ValidShiftStatusRule;
use App\Rules\ValidShiftTypeRule;
use App\Rules\ValidShiftUserRule;
use App\Rules\ValidUserCanWorkRule;
use Illuminate\Validation\Rule;

class ShiftRequest extends BaseRequest
{
    /**
     * rulesGet
     * handle rule method get
     *
     * @return array
     */
    public function rulesGet(): array
    {
        return [];
    }

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
        return [
            'branch_id' => 'nullable|integer|exists:branches,id,deleted_at,NULL',
            'user_id' => [
                'required',
                'integer',
                'exists:users,id,deleted_at,NULL',
                new ValidShiftUserRule($workDate, $branchId),
            ],
            'shift_type_id' => [
                'nullable',
                'integer',
                'exists:shift_types,id,deleted_at,NULL',
                'required_without:status',
            ],
            'status' => [
                'bail',
                'nullable',
                'integer',
                new ValidShiftStatusRule(),
            ],
            'work_date' => 'required|date_format:Y-m-d',
        ];
    }

    /**
     * rulesPut
     * handle rule method put
     *
     * @return array
     */
    public function rulesPut(): array
    {
        $request = request();
        $shift = $request->shift;
        $branchId = $request->branch_id;
        $workDate = $request->work_date;
        $shiftTypeId = $request->shift_type_id;
        $isManager = Common::checkCurrentAuthIsManager();

        $rules = [
            'status' => [
                'bail',
                'sometimes',
                'integer',
                new ValidShiftStatusRule($shift),
            ],
            'work_date' => 'required|date_format:Y-m-d',
        ];

        if ($isManager || (auth()->id() == $shift->user_id)) {
            $rules['shift_type_id'] = [
                'bail',
                'nullable',
                'integer',
                Rule::exists('shift_types', 'id')
                    ->whereNull('deleted_at')
                    ->where('branch_id', $branchId),
                'required_without:status',
                new ValidShiftTypeRule($shift)
            ];
        }

        if ($isManager) {
            $rules['user_id'] = [
                'bail',
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->whereNull('deleted_at')
                    ->where('branch_id', $branchId),
                new ValidUserCanWorkRule($shift, $workDate, $branchId, $shiftTypeId),
            ];
        }

        return $rules;
    }

    /**
     * @param $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (isset($this->shift_type_id)
                && isset($this->status)
                && Common::checkShiftStatusIsHoliday($this->status)
            ) {
                $message = __('message.shift.both_shift_type_and_status');
                $validator->errors()->add('shift_type_id', $message);
                $validator->errors()->add('status', $message);
            }
        });
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

    /**
     * merge data for validation
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        if ($this->method() === 'POST') {
            $this->merge([
                'user_id' => auth()->id(),
            ]);
            if (auth()->user()?->role == RoleEnum::BRANCH_MANAGER &&
                request()->status == ShiftStatusEnum::STATUS_WAITING_CONFIRM
            ) {
                $this->merge([
                    'status' => ShiftStatusEnum::STATUS_REGISTERED,
                ]);
            }
        }
    }
}
