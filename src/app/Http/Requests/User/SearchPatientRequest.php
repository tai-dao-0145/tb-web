<?php

namespace App\Http\Requests\User;

use App\Enums\Auth\RoleEnum;
use App\Helpers\Common;
use App\Http\Requests\BaseRequest;
use App\Models\Branch;
use Illuminate\Validation\Rule;

class SearchPatientRequest extends BaseRequest
{
    /**
     * @return string[]
     */
    public function rulesGet(): array
    {
        $facilityId = auth()->user()->facility_id;

        $rules = [
            'search' => 'sometimes|string|max:255',
            'column' => 'sometimes|string|max:255',
        ];


        if (Common::checkCurrentAuthIsFacilityManager()) {
            $rules['branch_id'] = [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->when($facilityId, fn($query) => $query->where('facility_id', $facilityId))
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
