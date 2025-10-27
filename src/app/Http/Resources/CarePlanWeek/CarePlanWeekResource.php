<?php

namespace App\Http\Resources\CarePlanWeek;

use App\Enums\AppEnum;
use App\Helpers\Common;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CarePlanWeekResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request request
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_name' => $this->full_name,
            'care_level_name' => $this->careLevel?->name,
            'services' => $this->carePlanWeeks
                ->groupBy(fn($row) => $row->service_id . '-' . $row->order)
                ->map(function ($items) {
                    $first = $items->first();
                    return [
                        'service_id' => (int)$first->service_id,
                        'order' => (int)$first->order,
                        'service_name' => $first->service->name,
                        'schedule' => $items
                            ->sortBy('weekday')
                            ->map(fn($row) => [
                                'weekday' => (int)$row->weekday,
                                'start_time' => Common::formatToDateTime($row->start_time, AppEnum::TIME_24H_FORMAT),
                                'end_time' => Common::formatToDateTime($row->end_time, AppEnum::TIME_24H_FORMAT),
                            ])
                            ->values(),
                    ];
                })
                ->sortBy(fn($item) => $item['order'])
                ->values(),
        ];
    }
}
