<?php

namespace App\Http\Resources\Shift;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ListShiftByStatusResource extends BaseResource
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
            'shift_id' => $this->id,
            'work_date' => $this->work_date,
        ];
    }
}
