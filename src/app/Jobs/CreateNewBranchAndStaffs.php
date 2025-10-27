<?php

namespace App\Jobs;

use App\Repositories\Interface\IBranchRepository;
use App\Repositories\Interface\IShiftEmployeeRepository;
use App\Repositories\Interface\IShiftStatusRepository;
use App\Repositories\Interface\IShiftTypeRepository;
use App\Repositories\Interface\IStaffShiftEmployeeRepository;
use App\Repositories\Interface\IStaffTypeBranchRepository;
use App\Repositories\Interface\IStaffTypeRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CreateNewBranchAndStaffs implements ShouldQueue
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
    public function __construct()
    {
        $this->year = 2025;
        $this->month = 6; // Tháng 6
    }

    /**
     * Execute the job.
     */
    public function handle(
        IShiftStatusRepository $shiftStatusRepository,
        IShiftTypeRepository $shiftTypeRepository,
        IStaffTypeBranchRepository $staffTypeBranchRepository,
        IStaffShiftEmployeeRepository $staffShiftEmployeeRepository,
        IStaffTypeRepository $staffTypeRepository,
        IBranchRepository $branchRepository,
        IShiftEmployeeRepository $shiftEmployeeRepository
    ): void {
        DB::beginTransaction();
        try {
            // $branch = $branchRepository->create([
            //     'name' => 'Chi nhánh mới',
            //     'address' => 'Địa chỉ chi nhánh mới',
            //     'phone' => '0123456789',
            //     'import_shift_type' => 0,
            //     'facility_id' => 1,
            // ]);
            $shiftStatus = $shiftStatusRepository->filterOne([
                'year' => $this->year,
                'month' => $this->month,
                'branch_id' => 19,
            ]);
            if ($shiftStatus) {
                return;
            }
            // Tạo mới trạng thái ca làm việc cho tháng này
            $shiftStatusRepository->create([
                'facility_id' => 1,
                'branch_id' => 19,
                'year' => $this->year,
                'month' => $this->month,
                'status' => 0,
            ]);
            // $shiftTypes = [
            //     ['name' => 'Ca sang', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'type' => '10', 'facility_id' => 1, 'branch_id' => 19],
            //     ['name' => 'Ca chieu', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'type' => '30', 'facility_id' => 1, 'branch_id' => 19],
            //     ['name' => 'Ca toi', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'type' => '40', 'facility_id' => 1, 'branch_id' => 19],
            // ];
            // $shiftTypeRepository->insert($shiftTypes);
            // Lấy tất cả shift types
            $shiftTypes = $shiftTypeRepository->getShiftTypes(19);
            // $staffTypes = $staffTypeRepository->getAll()->pluck('id')->toArray();
            // foreach ($staffTypes as $staffTypeId) {
            //     $staffTypeBranchRepository->create([
            //         'facility_id' => 1,
            //         'branch_id' => 19,
            //         'staff_type_id' => $staffTypeId,
            //     ]);
            // }
            $staffTypeBranch = $staffTypeBranchRepository->filter([
                'branch_id' => 19,
            ]);

            // Lấy số ngày trong tháng
            $daysInMonth = Carbon::createFromDate($this->year, $this->month, 1)->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $workDate = Carbon::createFromDate($this->year, $this->month, $day)->toDateString();

                foreach ($shiftTypes as $shiftType) {
                    $shiftEmployee = $shiftEmployeeRepository->create([
                        'branch_id' => 19,
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
