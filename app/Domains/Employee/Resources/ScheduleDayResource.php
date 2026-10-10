<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'day_of_week' => $this->day_of_week,

            'start_time' => $this->start_time,

            'end_time' => $this->end_time,

            'break_minutes' => $this->break_minutes,

            'required_minutes' => $this->required_minutes,

            'is_rest_day' => $this->is_rest_day,

            'remarks' => $this->remarks,

        ];
    }
}