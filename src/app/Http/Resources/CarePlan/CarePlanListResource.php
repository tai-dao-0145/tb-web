<?php

namespace App\Http\Resources\CarePlan;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CarePlanListResource extends BaseResource
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
            'care_plan_status' => $this['carePlanStatus'],
            ...CarePlanItemResource::collection($this['data'])
        ];
    }
}
