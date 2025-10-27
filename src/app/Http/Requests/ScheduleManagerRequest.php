<?php

namespace App\Http\Requests;

class ScheduleManagerRequest extends BaseRequest
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
            'year' => 'required_without_all:start_date,end_date|nullable|integer|min:2000|max:2100',
            'month' => 'required_without_all:start_date,end_date|nullable|integer|min:1|max:12',
            'start_date' => 'required_without_all:year,month|nullable|date_format:Y-m-d',
            'end_date' => 'required_with:start_date|nullable|date_format:Y-m-d|after_or_equal:start_date',
            'all_staff' => 'nullable|boolean',
            'group_id' => 'required_if:all_staff,false|integer|exists:groups,id,deleted_at,NULL',
            'branch_id' => 'required|integer|exists:branches,id,deleted_at,NULL',
            'only_get_shift_registration_submited_status' => 'nullable|boolean',
        ];
    }

    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        return [];
    }

    /**
     * rulesPut
     * handle rule method put
     *
     * @return array
     */
    public function rulesPut(): array
    {
        return [];
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
