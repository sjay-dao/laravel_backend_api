<?php

namespace App\Domains\Employee\Enums;

enum PayBasis:string
{
    case MONTHLY='MONTHLY';

    case DAILY='DAILY';

    case HOURLY='HOURLY';

    case PIECE_RATE='PIECE_RATE';

    case COMMISSION='COMMISSION';

    case MIXED='MIXED';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }
}