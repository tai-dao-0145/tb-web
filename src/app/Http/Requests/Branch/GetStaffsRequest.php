<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\BaseRequest;

class GetStaffsRequest extends BaseRequest
{
    /**
     * rulesGet
     * handle rule method get
     *
     * @return array
     */
    public function rulesGet(): array
    {
        return [
            'all_staff' => 'nullable|boolean',
            'group_id' => 'required_if:all_staff,false|integer|exists:groups,id',
            'branch_id' => 'nullable|integer|exists:branches,id,deleted_at,NULL,facility_id,' . auth()->user()?->facility_id,
            'is_working' => 'sometimes|boolean',
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
