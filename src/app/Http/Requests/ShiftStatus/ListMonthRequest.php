<?php

namespace App\Http\Requests\ShiftStatus;

use App\Http\Requests\BaseRequest;

class ListMonthRequest extends BaseRequest
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
            'branch_id' => 'required|integer|exists:branches,id',
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
