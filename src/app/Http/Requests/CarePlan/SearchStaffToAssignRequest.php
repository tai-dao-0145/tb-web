<?php

namespace App\Http\Requests\CarePlan;

use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class SearchStaffToAssignRequest extends BaseRequest
{
    /**
     * @return string[]
     */
    public function rulesGet(): array
    {
        $facilityId = auth()->user()->facility_id;

        $rules = [
            'name' => 'sometimes|string|max:255',
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
