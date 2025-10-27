<?php

namespace App\Rules;

use App\Enums\AppEnum;
use App\Models\ShiftType;
use App\Repositories\Interface\IShiftTypeRepository;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEndTimeIsWithinAShiftRule implements ValidationRule
{
    public function __construct()
    {
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $shiftTypeRepository = app(IShiftTypeRepository::class);
        $startDate = Carbon::parse(request()->start_time)->format(AppEnum::DATE_FORMAT);
        $branchId = auth()->user()->branch_id ?: request()->branch_id;
        $shiftTypeId = $shiftTypeRepository->getShiftTypeIdByDateTime($startDate, $branchId);

        if (!$shiftTypeId) {
            $fail(__('validation.care_plan.shift_type_not_found'));
            return;
        }

        $workTime = ShiftType::find($shiftTypeId);
        $startTime = $workTime->start_time;
        $endTime = $workTime->end_time;

        $endDateTime = Carbon::parse($startDate . ' ' . $endTime);

        if (Carbon::parse($startTime)->gte($endTime)) {
            $endDateTime->addDay();
        }

        if (Carbon::parse($value)->gt($endDateTime)) {
            $fail(__('validation.care_plan.end_time_in_one_shift_type'));
        }
    }
}
