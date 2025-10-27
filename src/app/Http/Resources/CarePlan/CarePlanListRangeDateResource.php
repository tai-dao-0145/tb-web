<?php

namespace App\Http\Resources\CarePlan;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CarePlanListRangeDateResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'more_info' => $this['moreInfo'],
            ...CarePlanGroupDateResource::collection($this['data'])
        ];
    }
}
