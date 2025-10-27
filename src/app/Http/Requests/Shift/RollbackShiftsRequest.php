<?php

namespace App\Http\Requests\Shift;

use App\Http\Requests\BaseRequest;

class RollbackShiftsRequest extends BaseRequest
{
    /**
     * Validation rules for rollback shifts request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
            'version_no' => 'required|integer|min:1|max:2000',
        ];
    }

    /**
     * Custom messages for validation rules.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return [];
    }
}
