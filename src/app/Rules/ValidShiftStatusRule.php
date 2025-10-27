<?php

namespace App\Rules;

use App\Enums\Shift\ShiftStatusEnum;
use App\Helpers\Common;
use App\Models\Shift;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidShiftStatusRule implements ValidationRule
{
    /**
     * @var Shift|null
     */
    protected ?Shift $shift;

    // Allowed statuses for staff
    protected const ALLOWED_UPDATE_STATUSES = [
        ShiftStatusEnum::STATUS_WAITING_CONFIRM,
        ShiftStatusEnum::STATUS_DESIRED_LEAVE,
        ShiftStatusEnum::STATUS_PAID_LEAVE,
    ];

    // Status transitions allowed by method for managers
    protected const MANAGER_VALID_OLD_STATUSES = [
        'POST' => self::ALLOWED_UPDATE_STATUSES,
        'PUT' => [
            ShiftStatusEnum::STATUS_WAITING_CONFIRM,
            ShiftStatusEnum::STATUS_REGISTERED,
            ShiftStatusEnum::STATUS_REQUEST,
            ShiftStatusEnum::STATUS_DESIRED_LEAVE,
            ShiftStatusEnum::STATUS_PAID_LEAVE,
        ],
    ];

    // Status transitions allowed by method for staff
    protected const STAFF_VALID_OLD_STATUSES = [
        'POST' => self::ALLOWED_UPDATE_STATUSES,
        'PUT' => self::ALLOWED_UPDATE_STATUSES,
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
        $newStatus = $value ?? ShiftStatusEnum::STATUS_WAITING_CONFIRM;
        $isStaff = Common::checkCurrentAuthIsStaff();

        // Check if status is allowed for update
        if ($isStaff and !in_array($newStatus, self::ALLOWED_UPDATE_STATUSES, true)) {
            $fail(__('validation.shift.invalid_shift_status'));
            return;
        }

        if (!$this->shift) {
            return;
        }

        $method = request()->method();
        $oldStatus = $this->shift->status;
        $isStaff = Common::checkCurrentAuthIsStaff();

        $validOldStatuses = $isStaff
            ? (self::STAFF_VALID_OLD_STATUSES[$method] ?? [])
            : (self::MANAGER_VALID_OLD_STATUSES[$method] ?? []);

        // Validate transition
        if (!in_array($oldStatus, $validOldStatuses, true)) {
            $fail(__('validation.shift.invalid_shift_status'));
        }
    }
}
