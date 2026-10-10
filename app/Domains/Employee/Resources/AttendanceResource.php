<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'employee_id' => $this->employee_id,

            'attendance_date' => optional(
                $this->attendance_date
            )->format('Y-m-d'),

            'status' => $this->status instanceof \BackedEnum
                ? $this->status->value
                : $this->status,

            'time_in' => $this->time_in,

            'time_out' => $this->time_out,

            'worked_minutes' => $this->worked_minutes,

            'late_minutes' => $this->late_minutes,

            'undertime_minutes' => $this->undertime_minutes,

            'overtime_minutes' => $this->overtime_minutes,

            'remarks' => $this->remarks,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,
        ];
    }
}