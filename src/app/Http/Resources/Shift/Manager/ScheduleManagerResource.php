<?php

namespace App\Http\Resources\Shift\Manager;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ScheduleManagerResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'shift_info' => $this['shift_info'],
            'data' => ScheduleDataResource::collection($this['data']),
        ];
    }
}
