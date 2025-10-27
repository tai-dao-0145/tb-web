<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\BaseRequest;

class DashboardInfoRequest extends BaseRequest
{
    /**
     * rulesGet
     * handle rule method get
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'branch_id' => [
                'bail',
                'sometimes',
                'integer',
                'exists:branches,id,deleted_at,NULL',
            ]
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
