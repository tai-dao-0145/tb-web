<?php

namespace App\Http\Resources\Shift;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ShiftResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facility_id' => $this->facility_id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'work_date' => $this->work_date,
            'shift_type_id' => $this->shift_type_id,
            'status' => $this->status,
        ];
    }
}
