<?php

namespace App\Http\Requests;

class ScheduleStaffRequest extends BaseRequest
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
            'year' => 'nullable|integer|min:2000|max:2100|required_without_all:start_date,end_date',
            'month' => 'required_with:year|nullable|integer|min:1|max:12|required_without_all:start_date,end_date',
            'start_date' => 'nullable|date_format:Y-m-d|required_without_all:year,month',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date|required_without_all:year,month',
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
