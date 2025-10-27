<?php

namespace App\Http\Resources\Shift\Manager;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ScheduleDataResource extends BaseResource
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
            'user_id' => $this['user_id'],
            'range_dates' => $this['range_dates'],
        ];
    }
}
