<?php

namespace App\Jobs;

use App\Enums\AppEnum;
use App\Enums\Branch\ImportShiftTypeEnum;
use App\Enums\CarePlan\TaskStatusEnum;
use App\Enums\Shift\ShiftStatusEnum;
use App\Enums\ShiftType\ShiftTypeEnum;
use App\Enums\CarePlan\TaskConfirmStatusEnum;
use App\Enums\User\ContinuousWorkEnum;
use App\Helpers\Common;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use App\Models\ShiftType;
use App\Models\ShiftStatus;
use App\Models\StaffType;
use App\Repositories\Interface\IBranchRepository;
use App\Repositories\Interface\ICarePlanConfirmRepository;
use App\Repositories\Interface\ICarePlanRepository;
use App\Repositories\Interface\IShiftEmployeeRepository;
use App\Repositories\Interface\IShiftRepository;
use App\Repositories\Interface\IShiftTypeRepository;
use App\Repositories\Interface\IStaffShiftEmployeeRepository;
use App\Repositories\Interface\IStaffTypeBranchRepository;
use App\Repositories\Interface\IStaffTypeUserRepository;
use App\Repositories\Interface\IUserRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateShifts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private ShiftStatus $shiftStatus;
    private Collection $usersWillBeProcessed;
    private array $submittedUserIds;
    // Define a list of shifts that need to be saved to the database
    private array $shiftsToSave = [];
    // Define the status of staff (including work date and total worked hours)
    private array $availableStaffStatus = [];
    // Use to check if the shifts have a leader
    private Collection $leaderShiftStatus;
    // Define a list of shift_employees that have leaders
    private array $hasLeaderShiftEmployeeIds = [];
    // Define a list of users with the is_leader attribute
    private Collection $isLeaderUsers;
    // Declare list of employees added to shift
    private array $assignedStaffsForShift;
    // Declare jobs in each shift
    private EloquentCollection|null $carePlans = null;
    private EloquentCollection|null $allShiftTypes = null;
    // Define a list of care plan confirms that need to be saved to the database
    private array $carePlanConfirmsToSave = [];
    // Define branch for shift status
    private Branch $branch;
    private string $startDate;
    private string $endDate;
    private array $shiftRegisteredQuery = [];
    private array $nightShiftTypeIds = [];
    private IBranchRepository $branchRepository;
    private IShiftTypeRepository $shiftTypeRepository;
    private IStaffTypeUserRepository $staffTypeUserRepository;
    private IStaffTypeBranchRepository $staffTypeBranchRepository;
    private IStaffShiftEmployeeRepository $staffShiftEmployeeRepository;
    private IShiftRepository $shiftRepository;
    private IUserRepository $userRepository;
    private ICarePlanRepository $carePlanRepository;
    private ICarePlanConfirmRepository $carePlanConfirmRepository;
    private IShiftEmployeeRepository $shiftEmployeeRepository;

    /**
     * Create a new job instance.
     */
    public function __construct($shiftStatus, $usersWillBeProcessed)
    {
        $this->shiftStatus = $shiftStatus;
        $this->usersWillBeProcessed = $usersWillBeProcessed;
        $this->branchRepository = app(IBranchRepository::class);
        $this->shiftTypeRepository = app(IShiftTypeRepository::class);
        $this->staffTypeUserRepository = app(IStaffTypeUserRepository::class);
        $this->staffTypeBranchRepository = app(IStaffTypeBranchRepository::class);
        $this->staffShiftEmployeeRepository = app(IStaffShiftEmployeeRepository::class);
        $this->shiftRepository = app(IShiftRepository::class);
        $this->userRepository = app(IUserRepository::class);
        $this->carePlanRepository = app(ICarePlanRepository::class);
        $this->carePlanConfirmRepository = app(ICarePlanConfirmRepository::class);
        $this->shiftEmployeeRepository = app(IShiftEmployeeRepository::class);
        // Get staffs who submitted shifts
        $this->submittedUserIds = $this->getSubmittedUserIds()->toArray();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the branch information based on the branch_id from shiftStatus
            $branch = $this->branchRepository->find($this->shiftStatus->branch_id);
            if (!$branch) {
                Log::error("Branch not found for ID: {$this->shiftStatus->branch_id}");
                return;
            }
            $this->branch = $branch;
            // Get the start and end dates based on the year and month from shiftStatus
            [$startDate, $endDate] = Common::getDateRangeFromConditions([
                'year' => $this->shiftStatus->year,
                'month' => $this->shiftStatus->month,
            ]);
            $this->startDate = $startDate;
            $this->endDate = $endDate;
            $this->shiftRegisteredQuery = [
                ['branch_id', '=', $this->shiftStatus->branch_id],
                ['status', '=', ShiftStatusEnum::STATUS_REGISTERED],
            ];
            // Get leader status of shifts
            $this->leaderShiftStatus = $this->shiftEmployeeRepository->filter([
                ['branch_id', $this->shiftStatus->branch_id],
                ['work_date', '>=', $startDate],
                ['work_date', '<=', $endDate],
            ])->groupBy(['work_date', 'shift_type_id']);
            // Get the staff types of the branch
            $staffTypeBranchIds = $this->staffTypeBranchRepository->getStaffTypeBranch($this->shiftStatus->branch_id)->pluck('staff_type_id')->toArray();
            // Get the staff requirements for the branch and date range
            $staffTypeRequirements = $this->staffShiftEmployeeRepository->getStaffRequirementByDate(
                $staffTypeBranchIds,
                $this->shiftStatus->branch_id,
                $startDate,
                $endDate,
                options: [
                    'sort' => [
                        ['work_date', 'asc'],
                        ['shift_type_id', 'desc'],
                    ],
                ]
            )->groupBy(['work_date', 'shift_type_id', 'staff_type_id']);
            // Get shift types
            $this->allShiftTypes = $this->shiftTypeRepository->getShiftTypes($this->shiftStatus->branch_id);
            // Get id of night shift types
            $this->nightShiftTypeIds = $this->allShiftTypes->where('type', (string)ShiftTypeEnum::NIGHT_SHIFT)->pluck('id')->toArray();
            // Get staffs who have assigned shifts in the month
            $staffRegistered = $this->shiftRepository->getStaffs(
                $this->shiftStatus->branch_id,
                Carbon::create($startDate)->subDay(),
                $endDate,
                options: [
                    'shift_status' => ShiftStatusEnum::STATUS_REGISTERED,
                    'sort' => [
                        ['work_date', 'asc'],
                        ['shift_type_id', 'desc'],
                    ],
                ]
            );
            // Get the degrees of staffs who will be assigned shifts
            $staffRegisteredDegrees = $this->staffTypeUserRepository->getList([
                'where' => [
                    ['branch_id', '=', $this->shiftStatus->branch_id],
                ],
                'user_ids' => $this->usersWillBeProcessed->toArray(),
            ])->groupBy('user_id');
            // Group the registered staff by work date, shift type, and staff type
            $staffRegisteredGroup = $staffRegistered->groupBy(['work_date', 'shift_type_id', 'staff_type_id']);
            // If the branch's import_shift_type is register both shifts and leave days
            if ($branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                // Get the list of staff who have registered shifts in the month
                $availableStaff = $this->shiftRepository->getStaffs(
                    $this->shiftStatus->branch_id,
                    $startDate,
                    $endDate,
                    options: [
                        'shift_status' => ShiftStatusEnum::STATUS_WAITING_CONFIRM,
                        'sort' => [
                            ['work_date', 'asc'],
                            ['shift_type_id', 'desc'],
                        ],
                        'user_ids' => $this->usersWillBeProcessed->toArray(),
                    ]
                );
                // Get id of staffs who have registered shifts in the month
                $availableStaffIds = $availableStaff->pluck('user_id')->unique();
                // Define the status of staff (including work date and total worked hours)
                $this->availableStaffStatus = $availableStaffIds->mapWithKeys(function ($item) {
                    return [$item => [
                        'work_date' => [],
                        'total_worked_hours' => 0,
                    ]];
                })->toArray();
                // Group the available staff by work date and shift type
                $availableStaffGroup = $availableStaff->groupBy(['work_date', 'shift_type_id']);
                foreach ($staffTypeRequirements as $workDate => $shiftTypes) {
                    $currentDate = Carbon::create($workDate);
                    $isWeekend = $currentDate->isWeekend();
                    // Assign staffs for night shifts
                    foreach ($shiftTypes as $shiftTypeId => $staffTypes) {
                        $this->assignedStaffsForShift = [];
                        if (in_array($shiftTypeId, $this->nightShiftTypeIds)) {
                            $this->assignShiftsToStaffForFacilityRegisterShiftsAndRequestLeaveDay(
                                $staffTypes,
                                $staffRegisteredGroup,
                                $workDate,
                                $shiftTypeId,
                                $availableStaffGroup,
                                $staffRegisteredDegrees,
                                isNightShift: true,
                                isWeekend: $isWeekend,
                            );
                            // Assign job to staffs
                            $this->assignStaffIntoJobs($workDate, $shiftTypeId, $this->shiftStatus->branch_id);
                        }
                    }
                    // Assign staffs for day shifts
                    foreach ($shiftTypes as $shiftTypeId => $staffTypes) {
                        $this->assignedStaffsForShift = [];
                        if (!in_array($shiftTypeId, $this->nightShiftTypeIds)) {
                            $this->assignShiftsToStaffForFacilityRegisterShiftsAndRequestLeaveDay(
                                $staffTypes,
                                $staffRegisteredGroup,
                                $workDate,
                                $shiftTypeId,
                                $availableStaffGroup,
                                $staffRegisteredDegrees,
                                isNightShift: false,
                                isWeekend: $isWeekend,
                            );
                            // Assign job to staffs
                            $this->assignStaffIntoJobs($workDate, $shiftTypeId, $this->shiftStatus->branch_id);
                        }
                    }
                }
                Log::info('------------------ Generate shifts for facility register shifts and request leave day successfully -----------------------');
            }
            // If the branch's import_shift_type is only register leave days
            if ($branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                $activeUsers = $this->userRepository->getActiveStaffs([
                    'select' => ['users.id', 'users.branch_id'],
                    'month' => $this->shiftStatus->month,
                    'year' => $this->shiftStatus->year,
                    'branch_id' => $this->shiftStatus->branch_id,
                    'sort' => [['users.id', 'asc']],
                    'user_ids' => $this->usersWillBeProcessed->toArray(),
                ]);
                // Get contract of staffs in the time range from startDate to endDate
                $activeUsers->load([
                    'staff:user_id,fixed_shift',
                    'staffContract' => function (Builder $query) use ($startDate) {
                        $query->whereDate('contracts.end_contract_at', '>=', $startDate)
                            ->where('branch_id', $this->shiftStatus->branch_id)
                            ->orderBy('is_leader', 'desc');
                    }
                ]);
                // Define a list of users with the is_leader attribute
                $this->isLeaderUsers = $activeUsers->mapWithKeys(function ($user) {
                    return [$user->id => [
                        'is_leader' => !empty($user->staffContract) ? $user->staffContract->is_leader : false,
                    ]];
                });
                // Remove contract information from active users to avoid unnecessary data transfer
                $activeUsers = $activeUsers->each(function ($model) {
                    unset($model->staffContract);
                });
                // Get degrees of staffs in the branch
                $staffRegisteredDegrees = $this->staffTypeUserRepository->getList([
                    'where' => [
                        ['branch_id', '=', $this->shiftStatus->branch_id],
                    ],
                    'user_ids' => $activeUsers->pluck('id')->toArray(),
                ])->groupBy('user_id');
                $availableStaffForEachDay = collect();
                // Define the status of staff (including work date and total worked hours)
                $this->availableStaffStatus = $activeUsers->pluck('id')->mapWithKeys(function ($item) {
                    return [$item => [
                        'work_date' => [],
                        'total_worked_hours' => 0,
                    ]];
                })->toArray();
                // Register for night shifts
                foreach ($staffTypeRequirements as $workDate => $shiftTypes) {
                    // Get available staffs for the current date
                    if (!$availableStaffForEachDay->has($workDate)) {
                        // Get unavailable staffs for the current date
                        $notAvailableStaffForCurrentDate = $this->shiftRepository->getStaffsByDate([
                            'work_date' => $workDate,
                            'where' => [
                                ['branch_id', '=', $this->shiftStatus->branch_id],
                            ]
                        ])->pluck('user_id')->unique()->toArray();
                        $availableStaffForEachDay->put($workDate, $activeUsers->whereNotIn('id', $notAvailableStaffForCurrentDate)->values());
                    }
                    $currentDate = Carbon::create($workDate);
                    $isWeekend = $currentDate->isWeekend();
                    // Assign staffs for night shifts
                    foreach ($shiftTypes as $shiftTypeId => $staffTypes) {
                        $this->assignedStaffsForShift = [];
                        if (in_array($shiftTypeId, $this->nightShiftTypeIds)) {
                            $this->assignShiftsToStaffForFacilityRequestLeaveDay(
                                $staffTypes,
                                $staffRegisteredGroup,
                                $workDate,
                                $shiftTypeId,
                                $availableStaffForEachDay,
                                $staffRegisteredDegrees,
                                isNightShift: true,
                                isWeekend: $isWeekend,
                            );
                            // Assign job to staffs
                            $this->assignStaffIntoJobs($workDate, $shiftTypeId, $this->shiftStatus->branch_id);
                        }
                    }
                    // Assign staffs for day shifts
                    foreach ($shiftTypes as $shiftTypeId => $staffTypes) {
                        $this->assignedStaffsForShift = [];
                        if (!in_array($shiftTypeId, $this->nightShiftTypeIds)) {
                            $this->assignShiftsToStaffForFacilityRequestLeaveDay(
                                $staffTypes,
                                $staffRegisteredGroup,
                                $workDate,
                                $shiftTypeId,
                                $availableStaffForEachDay,
                                $staffRegisteredDegrees,
                                isNightShift: false,
                                isWeekend: $isWeekend,
                            );
                            // Assign job to staffs
                            $this->assignStaffIntoJobs($workDate, $shiftTypeId, $this->shiftStatus->branch_id);
                        }
                    }
                }
                Log::info('------------------ Generate shifts for facility request leave day successfully -----------------------');
            }
            // Save to DB
            DB::beginTransaction();
            try {
                // If the branch's import_shift_type is register both shifts and leave days
                if ($branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                    foreach ($this->shiftsToSave as $shiftData) {
                        $this->shiftRepository->updateCondition([
                            'status' => ShiftStatusEnum::STATUS_REGISTERED,
                            'staff_type_id' => $shiftData['staff_type_id'],
                        ], Arr::only($shiftData, ['user_id', 'work_date', 'shift_type_id', 'branch_id']));
                    }
                }
                // If the branch's import_shift_type is only register leave days
                if ($branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                    $this->shiftRepository->insert($this->shiftsToSave);
                }
                // Update the shift status as completed
                $this->shiftStatus->auto_shift_assignment_status = 2;
                // Update the last auto-generated user ID
                $this->shiftStatus->last_auto_generated_user_id = $this->usersWillBeProcessed->last();
                $this->shiftStatus->save();
                // Set the has_leader attribute for shift employees
                $this->shiftEmployeeRepository->setHasLeader($this->hasLeaderShiftEmployeeIds);
                // Create care plan confirms
                if (count($this->carePlanConfirmsToSave)) {
                    $this->carePlanConfirmRepository->insert($this->carePlanConfirmsToSave);
                }
                DB::commit();
                Log::info('------------------ Generate shifts successfully -----------------------');
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
            // Find leader for shifts
            $this->findLeaderForShifts();
            // Replace staffs who work more hours than maximum
            $this->replaceStaffsWhoWorkMoreHoursThanMaximum();

            // Rule 6: Handle invalid streak days
            $this->handleInvalidStreakDays();

            return;
        } catch (Exception $e) {
            Log::info('-----------------------------------------');
            Log::error('AutoGenerateShifts Job failed: ' . $e->__toString());
            // Update the shift status as failed
            $this->shiftStatus->auto_shift_assignment_status = 3;
            $this->shiftStatus->save();
            return;
        }
    }

    /**
     * @return void
     */
    private function handleInvalidStreakDays(): void
    {
        $shifts = $this->shiftRepository->getListShifts([
            'select' => ['id', 'user_id', 'work_date', 'shift_type_id', 'staff_type_id', 'status', 'branch_id', 'facility_id'],
            'where' => [
                ...$this->shiftRegisteredQuery,
                ['work_date', '>=', $this->startDate],
                ['work_date', '<=', $this->endDate],
            ],
        ]);

        # Get invalid day streak
        $workingDayInvalidStreak5ByUserIds = $this->getListStreakDay($shifts);

        if (!empty($workingDayInvalidStreak5ByUserIds)) {
            foreach ($workingDayInvalidStreak5ByUserIds as $userId => $workingDayInvalidStreak5days) {
                $listShiftInvalidStreakDay = $this->shiftRepository->getListShifts([
                    'where' => [
                        ...$this->shiftRegisteredQuery,
                        ['user_id', '=', $userId],
                    ],
                    'whereIn' => [
                        'work_date' => $workingDayInvalidStreak5days
                    ]
                ]);
                $listShiftInvalidStreakDay->loadMissing('userContract');

                # Handle invalid day
                foreach ($listShiftInvalidStreakDay as $shift) {
                    $this->handleInvalidStreakShift($shift);
                }
            }
        }
    }

    /**
     * @param Shift $shift
     * @return void
     */
    private function handleInvalidStreakShift(Shift $shift): void
    {
        # List of employees available
        $availableStaffs = match ($this->branch->import_shift_type) {
            ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE => $this->getAvailableStaffsForShift(
                $shift->shift_type_id,
                $shift->work_date
            ),
            ImportShiftTypeEnum::REQUESTED_LEAVE => $this->getActiveUsersForCurrentDate($shift->work_date),
            default => collect()
        };

        if ($availableStaffs->isEmpty()) {
            return;
        }

        # List of employees eligible for replacement
        $availableStaffs = $this->getAvailableStaffsForShiftInWorkDate(
            $availableStaffs,
            $shift->work_date,
            $shift->shift_type_id
        );
        $availableStaffs->loadMissing('activeStaffContracts', 'staffTypeUsers');

        $shiftType = null;
        $isOnlyRequestedLeave = $this->branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE;
        if ($isOnlyRequestedLeave) {
            $shiftType = $this->allShiftTypes->firstWhere('id', $shift->shift_type_id);
            $availableStaffs->load([
                'staff:user_id,fixed_shift',
            ]);
        }
        $availableStaffs = $availableStaffs->filter(function ($staff) use ($shift, $isOnlyRequestedLeave, $shiftType) {
            return $staff->staffTypeUsers->contains('id', $shift->staff_type_id)
                && $staff->activeStaffContracts->contains(
                    fn ($contract) => $contract->end_contract_at >= $shift->work_date
                )
                && (
                    !$isOnlyRequestedLeave
                    // If staff has fixed shift that matches the current shift
                    || $this->isFixedShiftMatchShiftType($staff, $shiftType)
                );
        })->values();

        if ($availableStaffs->isEmpty()) {
            return;
        }

        $staffShiftHistories = $this->shiftRepository->getListShifts([
            'whereIn' => ['user_id' => $availableStaffs->pluck('id')],
            'where' => [
                ...$this->shiftRegisteredQuery,
                ['work_date', '>=', Carbon::parse($this->startDate)->subDays(5)->format(AppEnum::DATE_FORMAT)],
                ['work_date', '<=', $this->endDate],
            ],
        ])->groupBy('user_id');

        // Filter employees without a 6-day streak
        $filteredStaffs = $availableStaffs->filter(function ($staff) use ($staffShiftHistories, $shift) {
            $workDates = $staffShiftHistories[$staff->id] ?? null;
            if (!$workDates) {
                return false;
            }

            $isEligible = !$this->hasSixOrMoreConsecutiveDays($workDates, $shift);
            $hasEnoughHours = $this->hasMore160Hours($workDates, $shift);
            $isWeekend = Carbon::parse($shift->work_date)->isWeekend();
            $isLeader = $shift->userContract->is_leader;
            $onlyOneLeader = $this->countLeaderOfShiftType($shift) == 1;

            if (!$isWeekend && $onlyOneLeader && $isLeader) {
                $isEligible = $isEligible && $staff->activeStaffContracts
                        ->where('end_contract_at', '>=', $shift->work_date)
                        ->where('is_leader', true)
                        ->isNotEmpty();
            }
            return $isEligible && $hasEnoughHours;
        });

        if ($filteredStaffs->isNotEmpty()) {
            $this->replaceStaff($filteredStaffs, $shift, $shift->work_date);
        }
    }

    /**
     * Get the list of invalid streak days for continuous work.
     *
     * @param EloquentCollection $shifts
     * @return array
     */
    private function getListStreakDay(EloquentCollection $shifts): array
    {
        if ($shifts->isEmpty()) {
            return [];
        }

        $shiftDateByUserIds = [];
        foreach ($shifts as $shift) {
            if ($shift->shift_type_id && $shift->status == ShiftStatusEnum::STATUS_REGISTERED) {
                $isNightShift = in_array($shift->shift_type_id, $this->nightShiftTypeIds);
                $prevDate = date(AppEnum::DATE_FORMAT, strtotime("$shift->work_date -1 day"));
                $shiftDateByUserIds[$shift->user_id][$shift->work_date] = $isNightShift ? 2 : 1;
                $prevDateInvalid = isset($shiftDateByUserIds[$shift->user_id][$prevDate]) &&
                    $shiftDateByUserIds[$shift->user_id][$prevDate] == 2;
                if ($prevDateInvalid) {
                    // If the shift is night shift
                    $shiftDateByUserIds[$shift->user_id][$prevDate] = 1;
                }
            }
        }

        $workingDayInvalidStreak5 = [];

        foreach ($shiftDateByUserIds as $userId => $workingDays) {
            // Sort the working days by date
            ksort($workingDays);
            $streak = 0;
            $prevDate = null;
            foreach ($workingDays as $date => $workingDay) {
                $currDate = strtotime($date);

                if ($prevDate !== null) {
                    $diff = ($currDate - $prevDate) / 86400;

                    if ($diff > 1) {
                        $streak = 1;
                        $prevDate = $currDate;
                        continue;
                    }
                }

                $streak += $workingDay;

                if ($streak >= ContinuousWorkEnum::CONTINUOUS_WORK_LIMIT) {
                    $workingDayInvalidStreak5[$userId][] = $date;
                }

                $prevDate = $currDate;
            }
        }

        return $workingDayInvalidStreak5;
    }

    /**
     * @param Collection $currentShifts
     * @param Shift $newShift
     * @return bool
     */
    private function hasSixOrMoreConsecutiveDays(Collection $currentShifts, Shift $newShift): bool
    {
        $shifts = $currentShifts->push($newShift)
            ->unique('work_date')
            ->sortBy('work_date')
            ->pluck('work_date')
            ->map(fn ($date) => Carbon::parse($date))
            ->values();

        $workingDays = [];
        foreach ($shifts as $shift) {
            if ($shift->shift_type_id) {
                $isNightShift = in_array($shift->shift_type_id, $this->nightShiftTypeIds);
                $prevDate = date(AppEnum::DATE_FORMAT, strtotime("$shift->work_date -1 day"));
                $workingDays[$shift->work_date] = $isNightShift ? 2 : 1;
                $prevDateInvalid = isset($workingDays[$prevDate]) && $workingDays[$prevDate] == 2;
                if ($prevDateInvalid) {
                    // If the shift is night shift
                    $workingDays[$prevDate] = 1;
                }
            }
        }

        $streak = 0;
        $prevDate = null;
        foreach ($workingDays as $date => $workingDay) {
            $currDate = strtotime($date);

            if ($prevDate !== null) {
                $diff = ($currDate - $prevDate) / 86400;

                if ($diff > 1) {
                    $streak = 1;
                    $prevDate = $currDate;
                    continue;
                }
            }

            $streak += $workingDay;

            if ($streak >= ContinuousWorkEnum::CONTINUOUS_WORK_LIMIT) {
                return true;
            }

            $prevDate = $currDate;
        }

        return false;
    }

    /**
     * @param Collection $currentShifts
     * @param Shift $newShift
     * @return bool
     */
    private function hasMore160Hours(Collection $currentShifts, Shift $newShift): bool
    {
        $shifts = $currentShifts->push($newShift);
        $shifts->loadMissing('shiftType');

        $totalHours = $shifts->sum(function ($shift) {
            $start = $shift->shiftType->start_time;
            $end = $shift->shiftType->end_time;

            $startTime = Carbon::parse($start);
            $endTime = Carbon::parse($end);

            if ($endTime->gt($startTime)) {
                return $startTime->diffInHours($endTime);
            } else {
                return $startTime->diffInHours($endTime->copy()->addDay());
            }
        });

        return $totalHours > config('const.maximum_working_hours');
    }

    /**
     * @param Shift $shiftReplace
     * @return int
     */
    private function countLeaderOfShiftType(Shift $shiftReplace): int
    {
        $shifts = $this->shiftRepository->getListShifts([
            'select' => ['id', 'work_date', 'shift_type_id', 'has_leader'],
            'where' => [
                ...$this->shiftRegisteredQuery,
                ['work_date', '=', $shiftReplace->work_date],
                ['shift_type_id', '=', $shiftReplace->shift_type_id],
            ],
        ]);
        $shifts->loadMissing('userContract');

        return $shifts->whereHas('userContract', function ($query) {
            $query->where('is_leader', true);
        })->count();
    }


    /**
     * @return void
     */
    private function findLeaderForShifts(): void
    {
        $shiftType = null;
        // Get shifts which do not have leader
        $shiftEmployeesDoNotHaveLeader = $this->shiftEmployeeRepository->getShiftEmployees([
            'select' => ['id', 'work_date', 'shift_type_id', 'has_leader'],
            'where' => [
                ['branch_id', '=', $this->shiftStatus->branch_id],
                ['has_leader', '=', 0],
                ['work_date', '>=', $this->startDate],
                ['work_date', '<=', $this->endDate],
                [DB::raw("DAYOFWEEK(work_date)"), '!=', config('const.sunday_in_mysql')], // Exclude sunday
                [DB::raw("DAYOFWEEK(work_date)"), '!=', config('const.saturday_in_mysql')], // Exclude saturday
            ],
            'sort' => [
                ['work_date', 'asc'],
                ['shift_type_id', 'asc'],
            ],
        ]);
        // If there are no shift employees without leaders, return
        if (!$shiftEmployeesDoNotHaveLeader->count()) {
            return;
        }
        // Find leader for shifts
        foreach ($shiftEmployeesDoNotHaveLeader as $shiftEmployee) {
            // Get assigned shifts in current shift
            $shifts = $this->shiftRepository->getListShifts([
                'select' => ['id', 'user_id', 'work_date', 'shift_type_id', 'staff_type_id', 'status', 'branch_id', 'facility_id'],
                'where' => [
                    ...$this->shiftRegisteredQuery,
                    ['work_date', '=', $shiftEmployee->work_date],
                    ['shift_type_id', '=', $shiftEmployee->shift_type_id],
                ],
            ]);
            if (!$shifts->count()) {
                continue;
            }
            $availableStaffs = collect();
            // If the branch's import_shift_type is register both shifts and leave days
            if ($this->branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                // Get current shifts registered staffs
                $availableStaffs = $this->getAvailableStaffsForShift($shiftEmployee->shift_type_id, $shiftEmployee->work_date);
            }
            // If the branch's import_shift_type is only register leave days
            if ($this->branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                $shiftType = $this->allShiftTypes->firstWhere('id', $shiftEmployee->shift_type_id);
                // Get active staffs in current date
                $availableStaffs = $this->getActiveUsersForCurrentDate($shiftEmployee->work_date);
            }
            $availableStaffs = $this->getAvailableStaffsForShiftInWorkDate($availableStaffs, $shiftEmployee->work_date, $shiftEmployee->shift_type_id);
            // If no available staffs, continue to next shiftEmployee
            if (!$availableStaffs->count()) {
                continue;
            }
            // Get degrees of staffs who submitted shifts
            $availableStaffsDegrees = $this->getSubmittedStaffDegrees();

            foreach ($shifts as $shift) {
                $availableStaffsForShift = collect();
                // Find staffs who are leader and have matching degree for the shift
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                    $availableStaffsForShift = $availableStaffs->filter(function ($item) use ($availableStaffsDegrees, $shift) {
                        return $item->is_leader && isset($availableStaffsDegrees[$item->user_id])
                            && $availableStaffsDegrees[$item->user_id]->contains('staff_type_id', $shift->staff_type_id);
                    });
                }
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                    $availableStaffs->load([
                        'staff:user_id,fixed_shift',
                    ]);
                    $availableStaffsForShift = $availableStaffs->filter(
                        function ($item) use ($availableStaffsDegrees, $shift, $shiftType) {
                            return $this->isLeaderUsers->get($item->user_id, collect())['is_leader']
                                // If staff has fixed shift that matches the current shift
                                && $this->isFixedShiftMatchShiftType($item, $shiftType)
                                && isset($availableStaffsDegrees[$item->user_id])
                                && $availableStaffsDegrees[$item->user_id]
                                    ->contains('staff_type_id', $shift->staff_type_id);
                        }
                    );
                }
                if ($availableStaffsForShift->count()) {
                    DB::beginTransaction();
                    try {
                        $this->replaceStaff($availableStaffsForShift, $shift, $shiftEmployee->work_date);
                        // Marks the shift employee as having a leader
                        $shiftEmployee->has_leader = 1;
                        $shiftEmployee->save();
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Error while assigning leader to shift employee: ' . $e->getMessage());
                    }
                    break;
                }
            }
        }
    }

    /**
     * Assign shifts to staffs for facility register shifts and request leave day
     * @param Collection $staffTypes
     * @param Collection $staffRegisteredGroup
     * @param string $workDate
     * @param int $shiftTypeId
     * @param Collection $availableStaffGroup
     * @param Collection $staffRegisteredDegrees
     * @param bool $isNightShift = false,
     * @param bool $isWeekend = false,
     */
    private function assignShiftsToStaffForFacilityRegisterShiftsAndRequestLeaveDay(
        Collection $staffTypes,
        Collection $staffRegisteredGroup,
        string     $workDate,
        int        $shiftTypeId,
        Collection $availableStaffGroup,
        Collection $staffRegisteredDegrees,
        bool       $isNightShift = false,
        bool       $isWeekend = false,
    ) {
        $previousWorkDate = Carbon::create($workDate)->subDay()->format(AppEnum::DATE_FORMAT);
        // Priority for staff types with degree requirements
        $staffTypesPriority = $staffTypes->sortKeysDesc();
        foreach ($staffTypesPriority as $staffTypeId => $requirement) {
            // Get the number of staff already registered for the current work date and shift type
            $registeredStaffNumber = $staffRegisteredGroup->get($workDate, collect())->get($shiftTypeId, collect())->get($staffTypeId, collect())->count();
            // Get the number of staff required for the current work date and shift type
            $requiredNumber = $requirement->sum('number');
            if ($requiredNumber <= $registeredStaffNumber) {
                continue;
            }
            // Calculate the number of staff still needed for the current work date and shift type
            $missingStaffNumber = $requiredNumber - $registeredStaffNumber;
            // Get the available staff for the current work date and shift type
            $availableStaffsInCurrentShiftType = $availableStaffGroup->get($workDate, collect())->get($shiftTypeId, collect());
            // If staff type does not require a degree, get all registered staff for the current shift
            if ($staffTypeId === StaffType::NO_QUALIFICATION_REQUIRED_ID) {
                $availableStaffHasMatchingDegreeRoot = $availableStaffsInCurrentShiftType;
            }
            // If staff type requires a degree, filter the list of registered staff with degrees matching the current staff type
            if ($staffTypeId !== StaffType::NO_QUALIFICATION_REQUIRED_ID) {
                $availableStaffHasMatchingDegreeRoot = $availableStaffsInCurrentShiftType->filter(function ($staff) use ($staffRegisteredDegrees, $staffTypeId) {
                    return isset($staffRegisteredDegrees[$staff->user_id]) && $staffRegisteredDegrees[$staff->user_id]->contains('staff_type_id', $staffTypeId);
                });
            }
            while ($missingStaffNumber) {
                if (!$isWeekend) {
                    $hasLeader = $this->leaderShiftStatus->get($workDate, collect())->get($shiftTypeId, collect())->min('has_leader');
                }
                $availableStaffHasMatchingDegree = $availableStaffHasMatchingDegreeRoot->filter(function ($staff) use ($workDate, $previousWorkDate) {
                    return
                        // If the employee does not have any work shift scheduled for the day
                        !isset($this->availableStaffStatus[$staff->user_id]['work_date'][$workDate])
                        // If the employee did not work the night shift the previous day
                        && (!isset($this->availableStaffStatus[$staff->user_id]['work_date'][$previousWorkDate]) || !$this->availableStaffStatus[$staff->user_id]['work_date'][$previousWorkDate]['has_night_shift']);
                });
                // If there is no employee who fits the criteria
                if (!$availableStaffHasMatchingDegree->count()) {
                    continue 2;
                }
                if (!$isWeekend && !$hasLeader) {
                    // Get the employees who are leaders
                    $leaderStaffs = $availableStaffHasMatchingDegree->filter(function ($staff) {
                        return $staff->is_leader;
                    });
                }
                if (!$isWeekend && $hasLeader) {
                    // Get the employees who are not leaders
                    $unLeaderStaffs = $availableStaffHasMatchingDegree->filter(function ($staff) {
                        return !$staff->is_leader;
                    });
                }
                // If the current day is a weekday and there is no leader in the shift, prioritize choosing a leader employee
                if (!$isWeekend && !$hasLeader && $leaderStaffs->count()) {
                    $isChosenStaff = $leaderStaffs->random();
                    $this->leaderShiftStatus[$workDate][$shiftTypeId][0]['has_leader'] = 1;
                    $this->hasLeaderShiftEmployeeIds[] = $this->leaderShiftStatus[$workDate][$shiftTypeId][0]['id'];
                } // If today is a weekday and a leader is already assigned to the shift, prefer employees who are not leaders
                elseif (!$isWeekend && $hasLeader && $unLeaderStaffs->count()) {
                    $isChosenStaff = $unLeaderStaffs->random();
                } // If today is the weekend or a weekday but no prioritized employees are chosen, randomly select an available employee
                else {
                    // Select a random employee from the list of employees who have suitable qualifications
                    $isChosenStaff = $availableStaffHasMatchingDegree->random();
                }
                // Remove the selected employee from the list of employees with matching qualifications
                $availableStaffHasMatchingDegree = $availableStaffHasMatchingDegree->reject(function ($staff) use ($isChosenStaff) {
                    return $staff->user_id === $isChosenStaff->user_id;
                });
                // Assign the shift to the selected employee
                $this->availableStaffStatus[$isChosenStaff->user_id]['work_date'][$workDate] = [
                    'has_shift' => 1,
                    'has_night_shift' => $isNightShift,
                    'shift_type_id' => $shiftTypeId,
                    'staff_type_id' => $staffTypeId,
                ];
                // Keep a list of employees assigned to positions during a shift
                if (!isset($this->assignedStaffsForShift[$staffTypeId])) {
                    $this->assignedStaffsForShift[$staffTypeId] = collect();
                }
                $this->assignedStaffsForShift[$staffTypeId]->push($isChosenStaff->user_id);
                // Add the assigned shift information of the employee to save into the database
                $this->shiftsToSave[] = [
                    'user_id' => $isChosenStaff->user_id,
                    'work_date' => $workDate,
                    'shift_type_id' => $shiftTypeId,
                    'branch_id' => $this->shiftStatus->branch_id,
                    'staff_type_id' => $staffTypeId,
                ];
                // Update the number of employees still needed
                $missingStaffNumber--;
            }
        }
    }

    /**
     * Assign shifts to staffs for facility request leave day
     * @param Collection $staffTypes
     * @param Collection $staffRegisteredGroup
     * @param string $workDate
     * @param int $shiftTypeId
     * @param Collection $availableStaffForEachDay
     * @param Collection $staffRegisteredDegrees
     * @param bool $isNightShift default is false
     * @param bool $isWeekend default is false
     */
    private function assignShiftsToStaffForFacilityRequestLeaveDay(
        Collection $staffTypes,
        Collection $staffRegisteredGroup,
        string     $workDate,
        int        $shiftTypeId,
        Collection $availableStaffForEachDay,
        Collection $staffRegisteredDegrees,
        bool       $isNightShift = false,
        bool       $isWeekend = false,
    ) {
        $shiftType = $this->allShiftTypes->firstWhere('id', $shiftTypeId);
        $previousWorkDate = Carbon::create($workDate)->subDay()->format(AppEnum::DATE_FORMAT);
        // Prioritize shifts that require specific qualifications
        $staffTypesPriority = $staffTypes->sortKeysDesc();
        foreach ($staffTypesPriority as $staffTypeId => $requirement) {
            // Get the number of employees who have registered for the current workday and shift
            $registeredStaffNumber = $staffRegisteredGroup->get($workDate, collect())
                ->get($shiftTypeId, collect())->get($staffTypeId, collect())->count();
            // Get the number of employees needed for the current workday and shift
            $requiredNumber = $requirement->sum('number');
            if ($requiredNumber <= $registeredStaffNumber) {
                continue;
            }
            $missingStaffNumber = $requiredNumber - $registeredStaffNumber;
            $availableStaffsInCurrentShiftType = $availableStaffForEachDay->get($workDate, collect());
            // If the staff type does not require qualifications, take all registered employees
            if ($staffTypeId === StaffType::NO_QUALIFICATION_REQUIRED_ID) {
                $availableStaffHasMatchingDegreeRoot = $availableStaffsInCurrentShiftType;
            }
            // If the staff type requires qualifications, filter the registered employees by the current staff type
            if ($staffTypeId !== StaffType::NO_QUALIFICATION_REQUIRED_ID) {
                $availableStaffHasMatchingDegreeRoot = $availableStaffsInCurrentShiftType
                    ->filter(
                        function ($staff) use ($staffRegisteredDegrees, $staffTypeId) {
                            // If the employee has already registered and has a qualification that matches the current staff type
                            return isset($staffRegisteredDegrees[$staff->id])
                                && $staffRegisteredDegrees[$staff->id]->contains('staff_type_id', $staffTypeId);
                        }
                    );
            }
            while ($missingStaffNumber) {
                if (!$isWeekend) {
                    $hasLeader = $this->leaderShiftStatus->get($workDate, collect())->get($shiftTypeId, collect())->min('has_leader');
                }
                $availableStaffHasMatchingDegree = $availableStaffHasMatchingDegreeRoot->filter(
                    function ($staff) use ($workDate, $previousWorkDate, $shiftType) {
                        return
                            // If the employee has not worked any shifts on the current day
                            !isset($this->availableStaffStatus[$staff->id]['work_date'][$workDate])
                            // If the employee did not work the night shift the previous day
                            && (
                                !isset($this->availableStaffStatus[$staff->id]['work_date'][$previousWorkDate])
                                || !$this->availableStaffStatus[$staff->id]['work_date'][$previousWorkDate][
                                    'has_night_shift'
                                ]
                            )
                            // If staff has fixed shift that matches the current shift
                            && $this->isFixedShiftMatchShiftType($staff, $shiftType);
                    }
                )->values();
                // If there are no employees matching the requirements
                if (!$availableStaffHasMatchingDegree->count()) {
                    Log::info("Not enough available staff for date: {$workDate}, shift type: {$shiftTypeId}");
                    continue 2;
                }
                if (!$isWeekend && !$hasLeader) {
                    // Get the employees who are leaders
                    $leaderStaffs = $availableStaffHasMatchingDegree->filter(function ($staff) {
                        return $this->isLeaderUsers->get($staff->id, collect())['is_leader'];
                    });
                }
                if (!$isWeekend && $hasLeader) {
                    // Get the employees who are not leaders
                    $unLeaderStaffs = $availableStaffHasMatchingDegree->filter(function ($staff) {
                        return !$this->isLeaderUsers->get($staff->id, collect())['is_leader'];
                    });
                }
                // If the current day is a weekday and there is no leader in the shift, prioritize choosing a leader employee
                if (!$isWeekend && !$hasLeader && $leaderStaffs->count()) {
                    $isChosenStaff = $leaderStaffs->random();
                    $this->leaderShiftStatus[$workDate][$shiftTypeId][0]['has_leader'] = 1;
                    $this->hasLeaderShiftEmployeeIds[] = $this->leaderShiftStatus[$workDate][$shiftTypeId][0]['id'];
                } // If the current day is a weekday and there is already a leader in the shift, prioritize choosing a non-leader employee
                elseif (!$isWeekend && $hasLeader && $unLeaderStaffs->count()) {
                    $isChosenStaff = $unLeaderStaffs->random();
                } // If the current day is the weekend or a weekday but no prioritized employees are chosen, randomly select an employee who can work
                else {
                    // Randomly select an employee from the list of employees with matching qualifications
                    $isChosenStaff = $availableStaffHasMatchingDegree->random();
                }
                // Remove the chosen employee from the list of employees with matching qualifications
                $availableStaffHasMatchingDegree = $availableStaffHasMatchingDegree->reject(function ($staff) use ($isChosenStaff) {
                    return $staff->id === $isChosenStaff->id;
                });
                // Assign the shift to the chosen employee
                $this->availableStaffStatus[$isChosenStaff->id]['work_date'][$workDate] = [
                    'has_shift' => 1,
                    'has_night_shift' => $isNightShift,
                    'shift_type_id' => $shiftTypeId,
                    'staff_type_id' => $staffTypeId,
                ];
                // Keep a list of employees assigned to positions during a shift
                if (!isset($this->assignedStaffsForShift[$staffTypeId])) {
                    $this->assignedStaffsForShift[$staffTypeId] = collect();
                }
                $this->assignedStaffsForShift[$staffTypeId]->push($isChosenStaff->id);
                // Add the assigned shift information of the employee to save to the database
                $this->shiftsToSave[] = [
                    'facility_id' => $this->shiftStatus->facility_id,
                    'branch_id' => $this->shiftStatus->branch_id,
                    'user_id' => $isChosenStaff->id,
                    'work_date' => $workDate,
                    'shift_type_id' => $shiftTypeId,
                    'staff_type_id' => $staffTypeId,
                    'status' => ShiftStatusEnum::STATUS_REGISTERED,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // Update the number of employees still needed
                $missingStaffNumber--;
            }
        }
    }

    /**
     * Assign jobs to staffs
     * @param string $workDate
     * @param int $shiftTypeId
     * @param int $branchId
     */
    private function assignStaffIntoJobs(string $workDate, int $shiftTypeId, int $branchId)
    {
        // Get start time and end time of shifts
        [$startTime, $endTime] = $this->getWorkingTimeOfShift($shiftTypeId, $workDate);
        // Get jobs in the shifts
        $carePlans = $this->carePlanRepository->getCarePlans([
            'where' => [
                ['branch_id', $branchId],
                ['status', '!=', TaskStatusEnum::CANCELED],
                ['start_time', '>=', $startTime],
                ['end_time', '<=', $endTime],
            ],
            'sort' => [
                ['start_time', 'asc'],
                ['end', 'asc'],
            ],
        ]);
        $carePlans->load(['serviceStaffTypes', 'carePlanConfirms' => function ($query) {
            $query->where('status', '!=', TaskConfirmStatusEnum::REJECTED);
        }]);
        $previousCarePlan = null;
        $chosenStaffsForPreviousWorkingTime = [];
        $draftChosenStaffsForPreviousWorkingTime = [];
        foreach ($carePlans as $carePlan) {
            // Check working time duplication of jobs
            $isDuplicateWorkingTime = $previousCarePlan && $carePlan->start_time < $previousCarePlan->end_time;
            $draftChosenStaffsForPreviousWorkingTime = [];
            foreach ($carePlan->serviceStaffTypes ?? collect() as $serviceStaffType) {
                // Get required staff number
                $requiredNumber = $serviceStaffType->number;
                // Get assigned staff number
                $assignedStaffNumber = $carePlan->carePlanConfirms->where('staff_type_id', $serviceStaffType->staff_type_id)->count();
                $missingStaffNumber = $requiredNumber - $assignedStaffNumber;
                if ($missingStaffNumber <= 0) {
                    continue;
                }
                $availableStaffs = $this->assignedStaffsForShift[$serviceStaffType->staff_type_id] ?? collect();
                // Ignore employees assigned to work during the same working hours
                if ($isDuplicateWorkingTime) {
                    $availableStaffs = $availableStaffs->filter(function ($item) use ($chosenStaffsForPreviousWorkingTime) {
                        return !in_array($item, $chosenStaffsForPreviousWorkingTime);
                    });
                }
                if (!$availableStaffs->count()) {
                    continue;
                }
                while ($missingStaffNumber > 0 && $availableStaffs->count()) {
                    $isChosenStaff = $availableStaffs->random();
                    $availableStaffs = $availableStaffs->reject(function ($staffId) use ($isChosenStaff) {
                        return $staffId === $isChosenStaff;
                    });
                    $this->carePlanConfirmsToSave[] = [
                        'care_plan_id' => $carePlan->id,
                        'user_id' => $isChosenStaff,
                        'staff_type_id' => $serviceStaffType->staff_type_id,
                        'status' => TaskConfirmStatusEnum::PENDING,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $draftChosenStaffsForPreviousWorkingTime[] = $isChosenStaff;
                    $missingStaffNumber--;
                }
            }
            $previousCarePlan = $carePlan;
            // If working hours overlap, add the selected employee ID to the list of employees selected during the most recent working hours.
            if ($isDuplicateWorkingTime) {
                $chosenStaffsForPreviousWorkingTime = array_merge($chosenStaffsForPreviousWorkingTime, $draftChosenStaffsForPreviousWorkingTime);
                continue;
            }
            $chosenStaffsForPreviousWorkingTime = $draftChosenStaffsForPreviousWorkingTime;
        }
    }

    /**
     * Get working time of shift
     * @param int $shiftTypeId
     * @param string $workDate
     * @return array
     */
    private function getWorkingTimeOfShift(int $shiftTypeId, string $workDate): array
    {
        $shiftType = $this->allShiftTypes->firstWhere('id', $shiftTypeId);
        $startTime = $workDate . ' ' . $shiftType->start_time;
        $endTime = $workDate . ' ' . $shiftType->end_time;
        if ($shiftType->type == ShiftTypeEnum::NIGHT_SHIFT && $shiftType->start_time > $shiftType->end_time) {
            $endTime = Carbon::parse($workDate)->addDay()->format(AppEnum::DATE_FORMAT) . ' ' . $shiftType->end_time;
        }
        return [$startTime, $endTime];
    }

    /**
     * Replace staffs who work more hours than maximum
     */
    private function replaceStaffsWhoWorkMoreHoursThanMaximum(): void
    {
        $shiftType = null;
        $isRequiredLeader = false;
        $staffsWhoWorkMoreHoursThanMaximum = $this->getStaffsWhoWorkMoreHoursThanMaximum(config('const.maximum_working_hours'));
        if (!$staffsWhoWorkMoreHoursThanMaximum->count()) {
            Log::info('No staffs who work more hours than maximum');
            return;
        }
        $shiftHours = $this->shiftTypeRepository->getWorkingTimeOfShifts($this->shiftStatus->branch_id)->pluck('working_hours', 'id');
        $workingHours = 0;
        $shiftEmployees = $this->shiftEmployeeRepository->getShiftEmployees([
            'select' => ['id', 'work_date', 'shift_type_id', 'has_leader'],
            'where' => [
                ['branch_id', '=', $this->shiftStatus->branch_id],
                ['work_date', '>=', $this->startDate],
                ['work_date', '<=', $this->endDate],
                [DB::raw("DAYOFWEEK(work_date)"), '!=', config('const.sunday_in_mysql')], // Exclude sunday
                [DB::raw("DAYOFWEEK(work_date)"), '!=', config('const.saturday_in_mysql')], // Exclude saturday
            ],
        ]);
        // Get degrees of staffs who submitted shifts
        $availableStaffsDegrees = $this->getSubmittedStaffDegrees();

        foreach ($staffsWhoWorkMoreHoursThanMaximum as $staffId => $totalHours) {
            $assignedShiftsForCurrentStaff = $this->shiftRepository->getListShifts([
                'select' => ['id', 'user_id', 'work_date', 'shift_type_id', 'staff_type_id', 'status', 'branch_id', 'facility_id'],
                'where' => [
                    ...$this->shiftRegisteredQuery,
                    ['user_id', '=', $staffId],
                    ['work_date', '>=', $this->startDate],
                    ['work_date', '<=', $this->endDate],
                ],
                'sort' => [
                    ['work_date', 'desc'],
                ]
            ]);

            if (!$assignedShiftsForCurrentStaff->count()) {
                continue;
            }
            $assignedShiftsForCurrentStaff->load(['staffsInTheSameShift']);

            foreach ($assignedShiftsForCurrentStaff as $shift) {
                if ($totalHours <= config('const.maximum_working_hours')) {
                    break;
                }
                $workingHours = $shiftHours[$shift->shift_type_id] ?? 0;
                // If the branch's import_shift_type is register both shifts and leave days
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                    // Get current shifts registered staffs
                    $availableStaffs = $this->getAvailableStaffsForShift($shift->shift_type_id, $shift->work_date);
                }
                // If the branch's import_shift_type is only register leave days
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                    $shiftType = $this->allShiftTypes->firstWhere('id', $shift->shift_type_id);
                    // Get active staffs in current date
                    $availableStaffs = $this->getActiveUsersForCurrentDate($shift->work_date);
                }
                // Check if current shift require a leader
                $isRequiredLeader = $this->isRequiredLeader($shiftEmployees, $shift, $staffId);
                // Find staffs who have matching degree for the shift
                $availableStaffs = $this->getAvailableStaffsForShiftInWorkDate($availableStaffs, $shift->work_date, $shift->shift_type_id);
                // Get staffs who work more hours than maximum - working hours of shift
                $unavailableStaffs = $this->getStaffsWhoWorkMoreHoursThanMaximum(config('const.maximum_working_hours') - $workingHours);
                // Get staffs who work less hours than maximum - working hours of shift
                $availableStaffs = $availableStaffs->filter(function ($item) use ($unavailableStaffs) {
                    return !isset($unavailableStaffs[$item->user_id]);
                });

                $availableStaffsForShift = collect();
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
                    $availableStaffsForShift = $availableStaffs->filter(function ($item) use ($availableStaffsDegrees, $shift, $isRequiredLeader) {
                        return
                            // Find staffs who have matching degree for the shift
                            isset($availableStaffsDegrees[$item->user_id])
                            && $availableStaffsDegrees[$item->user_id]->contains('staff_type_id', $shift->staff_type_id)
                            // Find a team leader if the shift requires a team leader
                            && ($isRequiredLeader ? $item->is_leader : true);
                    });
                }
                if ($this->branch->import_shift_type === ImportShiftTypeEnum::REQUESTED_LEAVE) {
                    $availableStaffs->load([
                        'staff:user_id,fixed_shift',
                    ]);
                    $availableStaffsForShift = $availableStaffs->filter(
                        function ($item) use ($availableStaffsDegrees, $shift, $isRequiredLeader, $shiftType) {
                            return
                                // Find staffs who have matching degree for the shift
                                isset($availableStaffsDegrees[$item->user_id])
                                && $availableStaffsDegrees[$item->user_id]
                                    ->contains('staff_type_id', $shift->staff_type_id)
                                // If staff has fixed shift that matches the current shift
                                && $this->isFixedShiftMatchShiftType($item, $shiftType)
                                // Find a team leader if the shift requires a team leader
                                && (
                                    $isRequiredLeader
                                    ? $this->isLeaderUsers->get($item->user_id, collect())['is_leader']
                                    : true
                                );
                        }
                    );
                }
                if ($availableStaffsForShift->count()) {
                    DB::beginTransaction();
                    try {
                        $totalHours -= $workingHours;
                        $this->replaceStaff($availableStaffsForShift, $shift, $shift->work_date);
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Error when replacing employees exceeding maximum working hours per month: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get user contracts in current date
     * @param string $workDate
     * @param array $userIds
     * @return Collection
     */
    private function getUserContractsInCurrentDate(string $workDate, array $userIds = []): Collection
    {
        // Get active staffs in current date
        $options = [
            'select' => ['users.id as user_id', 'users.id', 'users.branch_id'],
            'start_date' => $workDate,
            'end_date' => $workDate,
            'branch_id' => $this->shiftStatus->branch_id,
            'sort' => [['users.id', 'asc']],
        ];
        if (count($userIds)) {
            $options['user_ids'] = $userIds;
        }
        $availableStaffs = $this->userRepository->getActiveStaffs($options);
        // Get contract of staffs in current date
        $availableStaffs->load([
            'staffContract' => function (Builder $query) use ($workDate) {
                $query->whereDate('contracts.end_contract_at', '>=', $workDate)
                    ->where('branch_id', $this->shiftStatus->branch_id)
                    ->orderBy('is_leader', 'desc');
            }
        ]);
        $this->isLeaderUsers = $availableStaffs->mapWithKeys(function ($user) {
            return [$user->user_id => [
                'is_leader' => !empty($user->staffContract) ? $user->staffContract->is_leader : false,
            ]];
        });
        return $availableStaffs;
    }

    /**
     * Get user ids who have submitted shifts
     * @return Collection
     */
    private function getSubmittedUserIds(): Collection
    {
        return $this->shiftStatus->shiftStatusUsers()->get()->sortBy([
            ['id', 'asc'],
        ])->pluck('user_id');
    }

    /**
     * Get available staffs for shift
     * @param int $shiftTypeId
     * @param string $workDate
     * @return Collection
     */
    private function getAvailableStaffsForShift(int $shiftTypeId, string $workDate): Collection
    {
        $options = [
            'shift_status' => ShiftStatusEnum::STATUS_WAITING_CONFIRM,
            'where' => [
                ['shift_type_id', '=', $shiftTypeId],
            ],
            'sort' => [
                ['work_date', 'asc'],
                ['shift_type_id', 'desc'],
            ],
        ];
        if (count($this->submittedUserIds)) {
            $options['user_ids'] = $this->submittedUserIds;
        }
        return $this->shiftRepository->getStaffs($this->shiftStatus->branch_id, $workDate, $workDate, options: $options);
    }

    /**
     * Get active staffs in current date
     * @param string $workDate
     * @return Collection
     */
    private function getActiveUsersForCurrentDate(string $workDate): Collection
    {
        // Get active staffs in current date
        $availableStaffs = $this->getUserContractsInCurrentDate($workDate, $this->submittedUserIds);
        // Get staffs who have shifts or requested leave day in current date
        $notAvailableStaffForCurrentDate = $this->shiftRepository->getStaffsByDate([
            'work_date' => $workDate,
            'where' => [
                ['branch_id', '=', $this->shiftStatus->branch_id],
            ]
        ])->pluck('user_id')->unique()->toArray();
        // Filter out staffs who are not available in current date
        return $availableStaffs->whereNotIn('id', $notAvailableStaffForCurrentDate)->values();
    }

    /**
     * Get available staffs for shift in work date
     * @param Collection $availableStaffs
     * @param string $workDate
     * @param int $shiftTypeId
     * @return Collection
     */
    private function getAvailableStaffsForShiftInWorkDate(
        Collection $availableStaffs,
        string     $workDate,
        int        $shiftTypeId
    ): Collection {
        $inValidStaffIds = [];
        // Get staffs who have shifts in last night
        $inValidStaffIds = array_merge($inValidStaffIds, $this->shiftRepository->getListShifts([
            'select' => ['id', 'user_id'],
            'where' => [
                ...$this->shiftRegisteredQuery,
                ['work_date', '=', Carbon::create($workDate)->subDay()->format(AppEnum::DATE_FORMAT)],
                ['shift_type_id', '=', ShiftTypeEnum::NIGHT_SHIFT],
            ],
        ])->pluck('user_id')->toArray());
        // Get staffs who have shifts in current date
        $inValidStaffIds = array_merge($inValidStaffIds, $this->shiftRepository->getListShifts([
            'select' => ['id', 'user_id'],
            'where' => [
                ...$this->shiftRegisteredQuery,
                ['work_date', '=', $workDate],
            ],
        ])->pluck('user_id')->toArray());
        // If current shift is night shift, get staffs who have shifts in next date
        if ($shiftTypeId == ShiftTypeEnum::NIGHT_SHIFT) {
            $inValidStaffIds = array_merge($inValidStaffIds, $this->shiftRepository->getListShifts([
                'select' => ['id', 'user_id'],
                'where' => [
                    ...$this->shiftRegisteredQuery,
                    ['work_date', '=', Carbon::create($workDate)->addDay()->format(AppEnum::DATE_FORMAT)],
                ],
            ])->pluck('user_id')->toArray());
        }
        return $availableStaffs->filter(function ($item) use ($inValidStaffIds) {
            return !in_array($item->user_id, $inValidStaffIds);
        });
    }

    /**
     * Replace staff in shift
     * @param Collection $availableStaffsForShift ,
     * @param Shift $shift ,
     * @param string $workDate
     */
    private function replaceStaff(Collection $availableStaffsForShift, Shift $shift, string $workDate): void
    {
        // Change shift for new staff
        $chosenStaff = $availableStaffsForShift->random();
        // Get working time of shift
        $workingTimeOfShift = $this->getWorkingTimeOfShift($shift->shift_type_id, $workDate);
        // Transfer of work from current employee to new employee
        $this->carePlanConfirmRepository->changeStaffForCarePlanConfirms(
            $shift->user_id,
            $chosenStaff->user_id,
            $this->shiftStatus->branch_id,
            $workingTimeOfShift
        );
        // Update or create shift for replacement employee
        $this->shiftRepository->updateOrCreate([
            'status' => ShiftStatusEnum::STATUS_REGISTERED,
            'staff_type_id' => $shift->staff_type_id,
        ], [
            'facility_id' => $shift->facility_id,
            'branch_id' => $shift->branch_id,
            'user_id' => $chosenStaff->user_id,
            'work_date' => $shift->work_date,
            'shift_type_id' => $shift->shift_type_id,
        ]);
        // Revert to previous state for newly replaced employee
        $previousStatus = $shift->latestShiftHistory?->previous_status;
        !is_null($previousStatus)
            ? $shift->update(['status' => $previousStatus])
            : $shift->delete();
    }

    // Get degrees of staffs who have shift submitted in the branch
    private function getSubmittedStaffDegrees(): Collection
    {
        return $this->staffTypeUserRepository->getList([
            'where' => [
                ['branch_id', '=', $this->shiftStatus->branch_id],
            ],
            'user_ids' => $this->submittedUserIds,
        ])->groupBy('user_id');
    }

    /**
     * Check if a leader is required for the shift
     * @param Collection $shiftEmployees
     * @param Shift $shift
     * @param int $staffId
     * @return bool
     */
    private function isRequiredLeader(Collection $shiftEmployees, Shift $shift, int $staffId): bool
    {
        $isRequiredLeader = false;
        $leaderNumberInCurrentShift = 0;
        $currentShiftHadHasLeader = $shiftEmployees->where('work_date', $shift->work_date)
            ->where('shift_type_id', $shift->shift_type_id)
            ->where('has_leader', 1)
            ->count();
        if (!$currentShiftHadHasLeader) {
            return $isRequiredLeader;
        }
        if ($this->branch->import_shift_type === ImportShiftTypeEnum::SHIFT_AND_REQUESTED_LEAVE) {
            $this->getUserContractsInCurrentDate(
                $shift->work_date,
                $shift->staffsInTheSameShift->pluck('user_id')->toArray()
            );
        }
        // Get leader number in current shift
        $leaderNumberInCurrentShift = $shift->staffsInTheSameShift->filter(function ($item) {
            return $this->isLeaderUsers->get($item->user_id, collect())['is_leader'] ?? 0;
        })->count();
        if (
            // If the current shift has a leader and the staff is a leader
            $leaderNumberInCurrentShift == 1
            && $this->isLeaderUsers->get($staffId, collect())['is_leader'] ?? 0
        ) {
            $isRequiredLeader = true;
        }
        return $isRequiredLeader;
    }

    /**
     * Get staffs who work more hours than maximum
     * @param int $maximumHours
     * @return Collection
     */
    private function getStaffsWhoWorkMoreHoursThanMaximum(int $maximumHours): Collection
    {
        return $this->shiftRepository->getStaffsWhoWorkMoreHoursThanMaximum([
            'branch_id' => $this->shiftStatus->branch_id,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'maximum_hours' => $maximumHours ?? config('const.maximum_working_hours'),
        ])->pluck('total_hours', 'user_id');
    }

    /**
     * Check if staff has fixed shift that matches the current shift
     * @param User      $staff     Staff to check
     * @param ShiftType $shiftType Shift type to check
     * @return bool
     */
    private function isFixedShiftMatchShiftType(User $staff, ShiftType $shiftType): bool
    {
        return
            $staff->staff?->fixed_shift
            && $staff->staff?->fixed_shift === $shiftType->type;
    }
}
