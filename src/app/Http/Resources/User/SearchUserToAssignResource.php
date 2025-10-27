<?php

namespace App\Http\Resources\User;

use App\Enums\Auth\RoleEnum;
use App\Http\Resources\BaseResource;
use App\Services\Interface\IUserService;
use Illuminate\Http\Request;

class SearchUserToAssignResource extends BaseResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $userService = app(IUserService::class);
        return [
            'id' => $this->id,
            'full_name' => $this->display_name ?? $this->full_name,
            'gender' => $this->gender,
            'work_hours' => (int)$this->worked_hours,
            'continuous_work' => $userService->checkContinuousWork($this->listShiftInCurrentMonth),
            'group_name' => $this->group?->name,
            'is_leader' => $this->is_leader,
            'is_spot_staff' => $this->role->name === RoleEnum::SPOT_STAFF,
            'staff_labels' => $this->staff_labels,
        ];
    }
}
