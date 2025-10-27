<?php

namespace App\Http\Requests\CarePlan;

use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CarePlanConfirmRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rulesPut(): array
    {
        $facilityId = auth()->user()->facility_id;
        $branchId = auth()->user()->branch_id ?: request()->input('branch_id');

        $rules = [
            'user_ids' => [
                'required',
                'array',
            ],
            'user_ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->when($facilityId, fn ($query) => $query->where('facility_id', $facilityId))
                    ->where('branch_id', $branchId)
                    ->whereNull('deleted_at')
            ],
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
