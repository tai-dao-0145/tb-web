<?php

namespace App\Http\Requests\ShiftStatusUser;

use App\Http\Requests\BaseRequest;

class ShiftRegistrationSubmitedStatusRequest extends BaseRequest
{
    /**
     * @return string[]
     */
    public function rulesGet(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100',
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
