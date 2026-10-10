<?php

namespace App\Domains\Employee\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'PRESENT';

    case ABSENT = 'ABSENT';

    case LEAVE = 'LEAVE';

    case HOLIDAY = 'HOLIDAY';

    case REST_DAY = 'REST_DAY';

    case HALF_DAY = 'HALF_DAY';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }
}