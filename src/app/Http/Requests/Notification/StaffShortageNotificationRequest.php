<?php

namespace App\Http\Requests\Notification;

use App\Http\Requests\BaseRequest;

class StaffShortageNotificationRequest extends BaseRequest
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
            'year' => 'required_without_all:start_date,end_date|nullable|integer|min:2000|max:2100',
            'month' => 'required_without_all:start_date,end_date|nullable|integer|min:1|max:12',
            'start_date' => 'required_without_all:year,month|nullable|date_format:Y-m-d',
            'end_date' => 'required_with:start_date|nullable|date_format:Y-m-d|after_or_equal:start_date',
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
