<?php

namespace App\Http\Resources\StaffType;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class StaffTypeResource extends BaseResource
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
        ];
    }
}
