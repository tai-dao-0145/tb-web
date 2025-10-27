<?php

namespace App\Jobs;

use App\Repositories\Interface\IShiftEmployeeRepository;
use App\Repositories\Interface\IShiftStatusRepository;
use App\Repositories\Interface\IShiftTypeRepository;
use App\Repositories\Interface\IStaffShiftEmployeeRepository;
use App\Repositories\Interface\IStaffTypeBranchRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CreateNewShiftForSpecificMonth implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $year;
    protected int $month;

    /**
     * Create a new job instance.
     */
    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * Execute the job.
     */
    public function handle(
        IShiftStatusRepository $shiftStatusRepository,
        IShiftTypeRepository $shiftTypeRepository,
        IStaffTypeBranchRepository $staffTypeBranchRepository,
        IStaffShiftEmployeeRepository $staffShiftEmployeeRepository,
        IShiftEmployeeRepository $shiftEmployeeRepository
    ): void {
        DB::beginTransaction();
        try {
            $shiftStatus = $shiftStatusRepository->filterOne([
                'year' => $this->year,
                'month' => $this->month,
                'branch_id' => 1,
            ]);
            if ($shiftStatus) {
                return;
            }
            // Tạo mới trạng thái ca làm việc cho tháng này
            $shiftStatusRepository->create([
                'facility_id' => 1,
                'branch_id' => 1,
                'year' => $this->year,
                'month' => $this->month,
                'status' => 0,
            ]);
            // Lấy tất cả shift types
            $shiftTypes = $shiftTypeRepository->getShiftTypes(1);
            $staffTypeBranch = $staffTypeBranchRepository->filter([
                'branch_id' => 1,
            ]);

            // Lấy số ngày trong tháng
            $daysInMonth = Carbon::createFromDate($this->year, $this->month, 1)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $workDate = Carbon::createFromDate($this->year, $this->month, $day)->toDateString();

                foreach ($shiftTypes as $shiftType) {
                    $shiftEmployee = $shiftEmployeeRepository->create([
                        'branch_id' => 1,
                        'facility_id' => 1,
                        'shift_type_id' => $shiftType->id,
                        'work_date' => $workDate,
                    ]);
                    foreach ($staffTypeBranch as $staffType) {
                        $staffShiftEmployeeRepository->create([
                            'shift_employee_id' => $shiftEmployee->id,
                            'staff_type_id' => $staffType->staff_type_id,
                            'number' => rand(3, 5), // Số lượng nhân viên sẽ được cập nhật sau
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->__toString());
        }

    }
}
