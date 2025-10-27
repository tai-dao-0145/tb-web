<?php

namespace App\Http\Resources\ShiftType;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ShiftTypeResource extends BaseResource
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
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'type' => $this->type,
        ];
    }
}
