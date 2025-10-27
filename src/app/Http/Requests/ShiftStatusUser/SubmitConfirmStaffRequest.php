<?php

namespace App\Http\Requests\ShiftStatusUser;

use App\Http\Requests\BaseRequest;

class SubmitConfirmStaffRequest extends BaseRequest
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
            'user_id' => 'required|integer|exists:users,id,deleted_at,NULL',
            'shift_status_id' => 'required|integer|exists:shift_statuses,id,deleted_at,NULL',
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
