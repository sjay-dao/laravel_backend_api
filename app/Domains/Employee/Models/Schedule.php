<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\Employee\Models\ScheduleDay;
use App\Domains\Employee\Models\EmployeeScheduleAssignment;

class Schedule extends Model
{
    protected $fillable = [

        'code',

        'name',

        'type',

        'weekly_required_minutes',

        'minimum_daily_minutes',

        'maximum_daily_minutes',

        'earliest_start',

        'latest_start',

        'latest_end',

        'core_start',

        'core_end',

        'approval_required',

        'is_active',

        'description',

    ];

    protected $casts = [

        'approval_required' => 'boolean',

        'is_active' => 'boolean',

    ];

    public function days()
    {
        return $this->hasMany(
            ScheduleDay::class
        )->orderBy('day_of_week');
    }

    public function employeeAssignments()
    {
        return $this->hasMany(EmployeeScheduleAssignment::class);
    }
}