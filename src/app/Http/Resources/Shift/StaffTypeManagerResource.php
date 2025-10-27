<?php

namespace App\Http\Resources\Shift;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class StaffTypeManagerResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'staff_type_id' => $this['staff_type_id'],
            'range_dates' => $this->formatRangeDates($this['range_dates'] ?? []),
        ];
    }

    /**
     * Format range dates data
     */
    private function formatRangeDates($dateData): array
    {
        $rangeDates = [];

        foreach ($dateData as $date => $shiftTypes) {
            $rangeDates[] = [
                'date' => $date,
                'shift_types' => $this->formatShiftTypes($shiftTypes)
            ];
        }

        return $rangeDates;
    }

    /**
     * Format shift types data
     */
    private function formatShiftTypes($shiftTypesData): array
    {
        $shiftTypes = [];

        foreach ($shiftTypesData as $shiftTypeId => $data) {
            $shiftTypes[] = [
                'shift_type_id' => $shiftTypeId,
                'number_required' => $data['number'] ?? 0,
                'number_registered' => $data['registered'] ?? 0
            ];
        }

        return $shiftTypes;
    }
}
