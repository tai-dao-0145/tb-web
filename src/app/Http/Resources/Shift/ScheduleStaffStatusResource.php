<?php

namespace App\Http\Resources\Shift;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ScheduleStaffStatusResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $shiftInfo = $this['shift_info'];
        $shifts = $this['shifts'] ?? null;

        return [
            'shift_info' => $shiftInfo,
            ...ScheduleStaffResource::collection($shifts),
        ];
    }
}
