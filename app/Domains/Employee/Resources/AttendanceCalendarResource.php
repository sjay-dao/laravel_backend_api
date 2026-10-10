<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AttendanceCalendarResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'days' => $this->collection->map(function ($attendance) {

                return [

                    'id' => $attendance->id,

                    'date' => $attendance->attendance_date
                        ->format('Y-m-d'),

                    'status' => $attendance->status instanceof \BackedEnum
                        ? $attendance->status->value
                        : $attendance->status,

                ];

            })->values(),
        ];
    }
}