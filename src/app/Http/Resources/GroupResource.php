<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GroupResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'facility_id' => $this->facility_id,
            'branch_id' => $this->branch_id,
        ];
    }
}
