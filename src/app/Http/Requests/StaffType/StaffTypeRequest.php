<?php

namespace App\Http\Requests\StaffType;

use App\Http\Requests\BaseRequest;

class StaffTypeRequest extends BaseRequest
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
            'branch_id' => 'nullable|integer|exists:branches,id,deleted_at,NULL',
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
