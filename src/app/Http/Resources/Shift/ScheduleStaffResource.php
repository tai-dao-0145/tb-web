<?php

namespace App\Http\Resources\Shift;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ScheduleStaffResource extends BaseResource
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
            "date" => $this->work_date,
            "shift_id" => $this->id,
            "shift_type_id" => $this->shiftType?->id,
            "shift_type_name" => $this->shiftType?->name,
            "status" => $this->status,
            "is_streak_day" => $this->is_streak_day,
        ];
    }
}
