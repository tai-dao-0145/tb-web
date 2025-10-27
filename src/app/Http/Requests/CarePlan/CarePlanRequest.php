<?php

namespace App\Http\Requests\CarePlan;

use App\Enums\CarePlan\TaskTypeEnum;
use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use App\Rules\ValidEndTimeIsWithinAShiftRule;
use Illuminate\Validation\Rule;

class CarePlanRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rulesGet(): array
    {
        $branchId = request()->input('branch_id', auth()->user()->branch_id);
        return [
            'branch_id' => 'sometimes|integer|exists:branches,id,deleted_at,NULL',
            'work_date' => 'sometimes|string|date_format:Y-m-d',
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')
                    ->where('branch_id', $branchId)
                    ->whereNull('deleted_at')
            ],
        ];
    }

    /**
     * @return array
     */
    public function rulesPatch(): array
    {
        $carePlan = request()->care_plan;
        $rules = [];

        if ($carePlan->type == TaskTypeEnum::CUSTOMER) {
            $rules['reason'] = 'required|string|max:255';
        }

        return $rules;
    }

    /**
     * @return array
     */
    public function rulesPut(): array
    {
        $rules = [
            'start_time' => 'bail|required|date_format:Y-m-d H:i:s',
            'end_time' => [
                'bail',
                'required',
                'date_format:Y-m-d H:i:s',
                'after:start_time',
                new ValidEndTimeIsWithinAShiftRule()
            ],
        ];

        if (Common::checkCurrentAuthIsFacilityManager()) {
            $facilityId = auth()->user()->facility_id;
            $rules['branch_id'] = [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
                    ->whereNull('deleted_at')
            ];
        }

        return $rules;
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
