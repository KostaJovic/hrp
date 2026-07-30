<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum RecurrenceUnit: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function addTo(CarbonInterface $date, int $interval): CarbonInterface
    {
        return match ($this) {
            self::Day => $date->addDays($interval),
            self::Week => $date->addWeeks($interval),
            self::Month => $date->addMonthsNoOverflow($interval),
            self::Year => $date->addYearsNoOverflow($interval),
        };
    }
}
