<?php

namespace App\Http\Requests\Shift;

use App\Enums\Shift\ShiftStatusEnum;
use App\Http\Requests\BaseRequest;

class ListShiftByStatusRequest extends BaseRequest
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
            'status' => 'required|integer|in:' . ShiftStatusEnum::getRuleIn(),
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
