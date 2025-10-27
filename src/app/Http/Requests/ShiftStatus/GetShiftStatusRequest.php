<?php

namespace App\Http\Requests\ShiftStatus;

use App\Http\Requests\BaseRequest;

class GetShiftStatusRequest extends BaseRequest
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
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'get_submited_user_count' => 'nullable|integer|in:0,1',
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
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
