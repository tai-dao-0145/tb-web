<?php

namespace App\Jobs;

use App\Enums\CarePlanStatus\CarePlanStatusEnum;
use App\Models\CarePlanStatus;
use App\Models\ShiftStatus;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateDefaultStatusByMonthJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private int $facilityId;
    private int $branchId;
    private int $year;
    private int $month;

    public function __construct(int $facilityId, int $branchId, int $year, int $month)
    {
        $this->facilityId = $facilityId;
        $this->branchId = $branchId;
        $this->year = $year;
        $this->month = $month;
    }

    public function handle(): void
    {
        $now = now();
        $firstDay = Carbon::create($this->year, $this->month);
        $daysInMonth = $firstDay->daysInMonth;

        $inserts = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $inserts[] = [
                'branch_id' => $this->branchId,
                'work_date' => Carbon::create($this->year, $this->month, $day)->toDateString(),
                'status' => CarePlanStatusEnum::PENDING,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::beginTransaction();
        try {
            ShiftStatus::create([
                'facility_id' => $this->facilityId,
                'branch_id'   => $this->branchId,
                'year'        => $this->year,
                'month'       => $this->month,
            ]);

            CarePlanStatus::insert($inserts);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CreateDefaultStatusByMonthJob failed: ' . $e->__toString(), [
                'facility_id' => $this->facilityId,
                'branch_id'   => $this->branchId,
                'year'        => $this->year,
                'month'       => $this->month,
            ]);
        }
    }
}
