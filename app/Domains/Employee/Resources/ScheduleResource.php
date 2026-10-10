<?php

namespace App\Domains\Employee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Employee\Resources\ScheduleDayResource;


class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'code' => $this->code,

            'name' => $this->name,

            'type' => $this->type,

            'weekly_required_minutes' => $this->weekly_required_minutes,

            'minimum_daily_minutes' => $this->minimum_daily_minutes,

            'maximum_daily_minutes' => $this->maximum_daily_minutes,

            'earliest_start' => $this->earliest_start,

            'latest_start' => $this->latest_start,

            'latest_end' => $this->latest_end,

            'core_start' => $this->core_start,

            'core_end' => $this->core_end,

            'approval_required' => $this->approval_required,

            'is_active' => $this->is_active,

            'description' => $this->description,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

            'days' => ScheduleDayResource::collection(
                $this->whenLoaded('days')
            ),
        ];
    }
}