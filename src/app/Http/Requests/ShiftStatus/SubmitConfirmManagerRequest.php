<?php

namespace App\Http\Requests\ShiftStatus;

use App\Http\Requests\BaseRequest;

class SubmitConfirmManagerRequest extends BaseRequest
{
    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        return [
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
            'year' => 'required|integer|min:2000',
            'month' => 'required|integer|min:1|max:12',
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
