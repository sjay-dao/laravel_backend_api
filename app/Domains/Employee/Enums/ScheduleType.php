<?php

namespace App\Domains\Employee\Enums;

enum ScheduleType: string
{
    case FIXED = 'FIXED';

    case FLEXIBLE = 'FLEXIBLE';

    case ON_CALL = 'ON_CALL';

    case SHIFT = 'SHIFT';

    case FIELD = 'FIELD';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }
}