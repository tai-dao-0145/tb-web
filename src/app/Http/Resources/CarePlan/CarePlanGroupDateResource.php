<?php

namespace App\Http\Resources\CarePlan;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CarePlanGroupDateResource extends BaseResource
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
            ...CarePlanItemResource::collection($this)
        ];
    }
}
