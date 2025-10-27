<?php

namespace App\Http\Requests\Service;

use App\Http\Requests\BaseRequest;

class ServiceRequest extends BaseRequest
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
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL,facility_id,'
                . auth()->user()?->facility_id,
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
