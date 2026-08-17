<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;

use App\Domains\System\Models\User;

class EmployeeScheduleAssignment extends Model
{
    protected $fillable = [

        'employee_id',

        'schedule_id',

        'effective_from',

        'effective_to',

        'is_active',

        'assigned_by',

        'remarks',

    ];

    protected $casts = [

        'effective_from' => 'date',

        'effective_to' => 'date',

        'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {

                $q->whereNull('effective_to')
                  ->orWhere(
                      'effective_to',
                      '>=',
                      $date
                  );

            });
    }
}