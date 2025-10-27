<?php

namespace App\Rules;

use App\Enums\Shift\ShiftStatusEnum;
use App\Models\Shift;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidShiftUserRule implements ValidationRule
{
    protected string $workDate;
    protected int $branchId;
    protected ?int $shiftId;
    protected bool $isRequest;

    public function __construct(string $workDate, int $branchId, ?int $shiftId = null, bool $isRequest = false)
    {
        $this->workDate = $workDate;
        $this->branchId = $branchId;
        $this->shiftId = $shiftId;
        $this->isRequest = $isRequest;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('id', $value)
            ->whereStaffShiftFlag()
            ->first();

        if (!$user) {
            $fail(__('message.shift.invalid_user'));
            return;
        }

        $hasValidContract = $user->staffContracts()
            ->where('branch_id', $this->branchId)
            ->where(function ($q) {
                $q->whereNull('end_contract_at')
                    ->orWhere('end_contract_at', '>', $this->workDate);
            })
            ->exists();

        if (!$hasValidContract) {
            $fail(__('message.shift.invalid_contract'));
        }

        if (!isset($this->shiftId)) {
            $alreadyHasShiftQuery = Shift::where('user_id', $user->id)
                ->where('branch_id', $this->branchId)
                ->where('work_date', $this->workDate);

            if ($this->isRequest) {
                $alreadyHasShiftQuery->whereIn('status', [
                    ShiftStatusEnum::STATUS_WAITING_CONFIRM,
                    ShiftStatusEnum::STATUS_REGISTERED
                ]);
            }

            $alreadyHasShift = $alreadyHasShiftQuery->exists();

            if ($alreadyHasShift) {
                $fail(__('message.shift.duplicate_user_shift'));
            }
        }
    }
}
