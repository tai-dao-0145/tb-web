<?php

namespace App\Http\Resources\CarePlan;

use App\Helpers\Common;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CarePlanItemResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $staffType = $this->service?->serviceStaffTypes->pluck('staffType.name', 'staff_type_id')->toArray();
        $requiredStaffTypeNumber = $this->service?->serviceStaffTypes->pluck('number', 'staff_type_id')->toArray();

        $carePlanConfirms = $this->carePlanConfirms->map(function ($confirm) use (&$requiredStaffTypeNumber) {
            $confirm->user->staffTypeUsers->each(function ($staffTypeUser) use (&$requiredStaffTypeNumber) {
                if (isset($requiredStaffTypeNumber[$staffTypeUser->staff_type_id]) &&
                    $requiredStaffTypeNumber[$staffTypeUser->staff_type_id] > 0) {
                    $requiredStaffTypeNumber[$staffTypeUser->staff_type_id] -= 1;
                }
            });
            return [
                'confirm_id' => $confirm->id,
                'confirm_status' => $confirm->status,
                'user_id' => $confirm->user->id,
                'user_name' => $confirm->user?->display_name ?? $confirm->user?->full_name,
                'user_gender' => $confirm->user->gender,
            ];
        })->all();

        $totalMissingStaffTypes = 0;
        $missingStaffTypes = [];

        foreach ($requiredStaffTypeNumber as $staffTypeId => $number) {
            if ($number > 0) {
                $totalMissingStaffTypes += $number;
                $missingStaffTypes[] = [
                    'staff_type_id' => $staffTypeId,
                    'staff_type_name' => $staffType[$staffTypeId] ?? null,
                    'number' => $number,
                ];
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->display_name ?? $this->user?->full_name,
            'service_id' => $this->service_id,
            'service_name' => $this->service?->name,
            'total_missing_staff_types' => $totalMissingStaffTypes,
            'missing_staff_types' => $missingStaffTypes,
            'status' => $this->status,
            'start_time' => Common::formatToDateTime($this->start_time),
            'end_time' => Common::formatToDateTime($this->end_time),
            'location' => $this->location,
            'reason' => $this->reason,
            'pic' => $carePlanConfirms
        ];
    }
}
