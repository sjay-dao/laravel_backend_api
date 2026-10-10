<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleDay extends Model
{
    protected $fillable = [

        'schedule_id',

        'day_of_week',

        'start_time',

        'end_time',

        'break_minutes',

        'required_minutes',

        'is_rest_day',

        'remarks'

    ];

    protected $casts = [

        'is_rest_day' => 'boolean'

    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}