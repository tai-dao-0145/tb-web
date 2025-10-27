<?php

namespace App\Helpers;

use App\Enums\AppEnum;
use Carbon\Carbon;

class Common
{
    public static function isProd(): bool
    {
        return env('APP_ENV') === AppEnum::ENV_PRODUCTION;
    }

    public static function formatToDateTime($date, ?string $format = AppEnum::DATETIME_FORMAT): ?string
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }

    /**
     * Returns the start and end dates of a given month as an array of date strings.
     *
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public static function getMonthRange(?int $year = null, ?int $month = null): array
    {
        $date = !$year || !$month ? now() : Carbon::create($year, $month, 1);

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        return [$start, $end];
    }
}
