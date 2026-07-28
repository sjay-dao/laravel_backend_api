<?php

namespace App\Domains\Employee\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class ScheduleCalculator
{
    public function calculate(array $day): array
    {
        $isRestDay = (bool) ($day['is_rest_day'] ?? false);

        if ($isRestDay) {

            $day['start_time'] = null;
            $day['end_time'] = null;
            $day['break_minutes'] = 0;
            $day['required_minutes'] = 0;

            return $day;
        }

        if (
            empty($day['start_time']) ||
            empty($day['end_time'])
        ) {
            throw new InvalidArgumentException(
                'Start time and End time are required.'
            );
        }

        $start = Carbon::createFromFormat(
            'H:i',
            $day['start_time']
        );

        $end = Carbon::createFromFormat(
            'H:i',
            $day['end_time']
        );

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException(
                'End time must be later than start time.'
            );
        }

        $break = (int) ($day['break_minutes'] ?? 60);

        $minutes = $start->diffInMinutes($end) - $break;

        if ($minutes <= 0) {
            throw new InvalidArgumentException(
                'Invalid break minutes.'
            );
        }

        $day['required_minutes'] = $minutes;

        return $day;
    }
}