<?php

namespace App\Rules;

use App\Enums\ShiftType\ShiftTypeEnum;
use App\Models\Shift;
use App\Models\ShiftType;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidUserCanWorkRule implements ValidationRule
{
    protected Shift $shift;
    protected string $workDate;
    protected int $branchId;
    protected ?int $shiftTypeId;

    public function __construct(Shift $shift, string $workDate, int $branchId, ?int $shiftTypeId = null)
    {
        $this->shift = $shift;
        $this->workDate = $workDate;
        $this->branchId = $branchId;
        $this->shiftTypeId = $shiftTypeId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->shiftTypeId) {
            return;
        }

        $userId = $value;

        // Get night shift IDs for the branch (excluding current shiftTypeId)
        $nightShiftTypeIds = ShiftType::query()
            ->where('branch_id', $this->branchId)
            ->where('type', ShiftTypeEnum::NIGHT_SHIFT)
            ->pluck('id')
            ->toArray();

        $workDate = Carbon::parse($this->workDate);
        $yesterday = $workDate->copy()->subDay();
        $tomorrow = $workDate->copy()->addDay();

        // Rule 1: Cannot work if user had a night shift yesterday
        $hadNightShiftYesterday = Shift::query()
            ->whereDate('work_date', $yesterday)
            ->where('branch_id', $this->branchId)
            ->where('user_id', $userId)
            ->whereIn('shift_type_id', $nightShiftTypeIds)
            ->whereShiftRegistered()
            ->exists();

        if ($hadNightShiftYesterday) {
            $fail(__('validation.shift.staff_cannot_work_due_to_night_shift'));
            return;
        }

        // Rule 2: Cannot work night shift today if user has a shift tomorrow
        $isNightShiftToday = in_array($this->shiftTypeId, $nightShiftTypeIds);

        if ($isNightShiftToday) {
            $hasShiftTomorrow = Shift::query()
                ->whereDate('work_date', $tomorrow)
                ->where('branch_id', $this->branchId)
                ->where('user_id', $userId)
                ->whereShiftRegistered()
                ->exists();

            if ($hasShiftTomorrow) {
                $fail(__('validation.shift.staff_cannot_work_due_to_tomorrow_shift'));
            }
        }
    }
}
