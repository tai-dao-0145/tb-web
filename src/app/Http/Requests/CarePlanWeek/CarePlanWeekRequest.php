<?php

namespace App\Http\Requests\CarePlanWeek;

use App\Enums\AppEnum;
use App\Enums\CarePlanWeek\WeekdayEnum;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CarePlanWeekRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rulesPost(): array
    {
        return [
            'services' => [
                'present',
                'array',
                function ($attribute, $value, $fail) {
                    $check = [];
                    foreach ($value as $service) {
                        $key = $service['service_id'] . '-' . $service['order'];
                        if (isset($check[$key])) {
                            $fail(__('message.duplicate_service_order'));
                            return;
                        }
                        $check[$key] = true;
                    }

                    $allSchedules = [];
                    foreach ($value as $service) {
                        foreach ($service['schedule'] as $row) {
                            if (empty($row['start_time']) || empty($row['end_time'])) {
                                continue;
                            }
                            $allSchedules[] = [
                                'weekday' => $row['weekday'],
                                'start_time' => $row['start_time'],
                                'end_time' => $row['end_time'],
                            ];
                        }
                    }

                    foreach ($allSchedules as $currentIndex => $row) {
                        $start = Carbon::createFromFormat(AppEnum::TIME_24H_FORMAT, $row['start_time']);
                        $end = Carbon::createFromFormat(AppEnum::TIME_24H_FORMAT, $row['end_time']);

                        foreach ($allSchedules as $compareIndex => $other) {
                            if ($currentIndex === $compareIndex) continue;
                            if ($row['weekday'] != $other['weekday']) continue;

                            $otherStart = Carbon::createFromFormat(AppEnum::TIME_24H_FORMAT, $other['start_time']);
                            $otherEnd = Carbon::createFromFormat(AppEnum::TIME_24H_FORMAT, $other['end_time']);

                            if ($start <= $otherEnd && $end >= $otherStart) {
                                $fail(__('message.overlap_time'));
                                return;
                            }
                        }
                    }
                }
            ],
            'services.*.service_id' => [
                'required',
                Rule::exists('services', 'id')->whereNull('deleted_at'),
            ],
            'services.*.order' => [
                'required',
                'integer',
                'min:1',
            ],
            'services.*.schedule' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $weekdays = array_column($value, 'weekday');
                    $required = WeekdayEnum::getValues();
                    $diff = array_diff($required, $weekdays);
                    if (!empty($diff)) {
                        $fail(__('message.must_include_all_weekday'));
                    }
                }
            ],
            'services.*.schedule.*.weekday' => 'required|in:' . WeekdayEnum::getRuleIn(),
            'services.*.schedule.*.start_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:services.*.schedule.*.end_time',
            ],
            'services.*.schedule.*.end_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:services.*.schedule.*.start_time',
                'after:services.*.schedule.*.start_time',
            ],
        ];
    }

    /**
     * Custom message for rule
     *
     * @return array
     */
    public function getMessages(): array
    {
        return [];
    }
}
