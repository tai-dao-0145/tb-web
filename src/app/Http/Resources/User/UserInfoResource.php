<?php

namespace App\Http\Resources\User;

use App\Helpers\Common;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserInfoResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'full_name' => $this->full_name,
            'display_name' => $this->display_name ?? $this->full_name,
            'email' => $this->email ?? null,
            'role' => $this->role,
            'address' => $this->address,
            'shift_flag' => $this->staff?->shift_flag ?? 0,
            'facility_id' =>  $this->facility->id,
            'facility_name' =>  $this->facility->name,
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'last_login_at' => Common::formatToDateTime($this->last_login_at ?? null),
        ];
    }
}
