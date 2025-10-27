<?php

namespace App\Rules;

use App\Enums\Shift\ShiftStatusEnum;
use App\Models\Shift;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidShiftTypeRule implements ValidationRule
{
    /**
     * @var Shift|null
     */
    protected ?Shift $shift;

    // Current statuses that are valid for staff to change
    protected const STAFF_VALID_CURRENT_STATUSES = [
        ShiftStatusEnum::STATUS_WAITING_CONFIRM,
        ShiftStatusEnum::STATUS_DESIRED_LEAVE,
        ShiftStatusEnum::STATUS_PAID_LEAVE,
    ];

    /**
     * @param Shift|null $shift
     */
    public function __construct(?Shift $shift = null)
    {
        $this->shift = $shift;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->shift) {
            return;
        }

        $currentStatus = $this->shift->status;

        if (!in_array($currentStatus, self::STAFF_VALID_CURRENT_STATUSES, true)) {
            $fail(__('validation.shift.invalid_shift_type'));
        }
    }
}
