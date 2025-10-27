<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\BaseRequest;

class GetAvailableStaffRequest extends BaseRequest
{
    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
            'work_date' => 'required|string|date_format:Y-m-d',
            'shift_type_id' => 'required|integer|exists:shift_types,id,deleted_at,NULL,branch_id,'.request()->input('branch_id'),
            'staff_type_id' => 'required|integer|exists:staff_type_branches,staff_type_id,deleted_at,NULL,branch_id,'.request()->input('branch_id'),
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
