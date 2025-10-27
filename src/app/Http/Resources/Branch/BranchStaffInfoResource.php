<?php

namespace App\Http\Resources\Branch;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class BranchStaffInfoResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'full_name'    => $this->full_name,
            'display_name' => $this->display_name,
            'branch_id'    => $this->branch_id,
            'shift_flag'   => $this->whenLoaded('staff', function () {
                return $this->staff->shift_flag;
            }, null),
            'role_name'    => $this->role_name,
            'gender'       => $this->gender,
            'is_disabled'  => $this->is_disabled,
            'group'        => $this->whenLoaded('group', function () {
                return [
                    'id'   => $this->group->id,
                    'name' => $this->group->name,
                ];
            }, null),
            'current_contract' => $this->whenLoaded('currentStaffContract',
                fn () => $this->formatContract($this->currentStaffContract)),
            'latest_contract'  => $this->whenLoaded('latestStaffContract',
                fn () => $this->formatContract($this->latestStaffContract)),
        ];
    }

    /**
     * Format contract info
     *
     * @param mixed $contract
     * @return array<string, mixed>|null
     */
    protected function formatContract($contract): ?array
    {
        if (!$contract) {
            return null;
        }

        return [
            'id'        => $contract->id,
            'branch_id' => $contract->branch_id,
            'user_id'   => $contract->user_id,
            'is_leader' => $contract->is_leader,
            'signed_contract_at' => $contract->signed_contract_at,
            'end_contract_at'    => $contract->end_contract_at,
            'staff_labels' => ($contract->relationLoaded('staffLabels') && $contract->staffLabels->isNotEmpty())
                ? $contract->staffLabels
                    ->map(fn ($label) => [
                        'id'         => $label->id,
                        'name'       => $label->name,
                        'color_code' => $label->color_code,
                    ])->values()
                : null,
            'contract_type' => ($contract->relationLoaded('contractType') && $contract->contractType)
                ? [
                    'id'   => $contract->contractType->id,
                    'name' => $contract->contractType->name,
                ]
                : null,
        ];
    }
}
