<?php

namespace App\Http\Requests\CarePlan;

use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CarePlanStatusQueryMonthRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rulesGet(): array
    {
        $facilityId = auth()->user()->facility_id;

        $rules = [
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['required', 'date_format:Y-m', 'after:start_month'],
        ];

        if (Common::checkCurrentAuthIsFacilityManager()) {
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
