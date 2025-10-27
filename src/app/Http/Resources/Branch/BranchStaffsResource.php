<?php

namespace App\Http\Resources\Branch;

use App\Enums\Auth\RoleEnum;
use App\Http\Resources\BaseResource;
use App\Services\Interface\IUserService;
use Illuminate\Http\Request;

class BranchStaffsResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userService = app(IUserService::class);

        return [
            'id'              => $this->id,
            'full_name'       => $this->full_name,
            'display_name'    => $this->display_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'gender'          => $this->gender,
            'work_hours'      => (int)$this->worked_hours,
            'continuous_work' => $userService->checkContinuousWork($this->listShiftInCurrentMonth),
            'group_name'      => $this->group?->name,
            'is_leader'       => $this->is_leader,
            'is_spot_staff'   => $this->role->name === RoleEnum::SPOT_STAFF,
            'staff_labels'    => $this->staff_labels,
            'staff'           => $this->whenLoaded('staff', function () {
                return [
                    'id'          => $this->staff->id,
                    'user_id'     => $this->staff->user_id,
                    'fixed_shift' => $this->staff->fixed_shift,
                ];
            }, null),
        ];
    }
}
