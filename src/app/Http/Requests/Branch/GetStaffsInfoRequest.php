<?php

namespace App\Http\Requests\Branch;

use App\Http\Requests\BaseRequest;
use App\Enums\Contract\ContractStatusEnum;

class GetStaffsInfoRequest extends BaseRequest
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
            'branch_id' => 'sometimes|integer|exists:branches,id,deleted_at,NULL,facility_id,' . auth()->user()?->facility_id,
            'contract_status' => ['sometimes', 'string', 'in:' . ContractStatusEnum::getRuleIn()],
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
