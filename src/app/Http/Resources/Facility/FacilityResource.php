<?php

namespace App\Http\Resources\Facility;

use App\Http\Resources\BaseResource;
use App\Http\Resources\ShiftType\ShiftTypeResource;
use Illuminate\Http\Request;

class FacilityResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $branch = $this->whenLoaded('branches', function () {
            return $this->branches->first();
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legalEntity?->name ?? null,
            'shift_types' => ShiftTypeResource::collection($this->shiftTypes ?? []),
            'import_shift_type' => $branch?->import_shift_type,
        ];
    }
}
