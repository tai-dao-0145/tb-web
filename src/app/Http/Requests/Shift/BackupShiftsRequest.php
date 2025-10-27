<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\BaseRequest;

class BackupShiftsRequest extends BaseRequest
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
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
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
