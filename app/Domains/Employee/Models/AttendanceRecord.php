<?php

namespace App\Domains\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Domains\Employee\Models\Employee;
use App\Models\User;
use App\Domains\Employee\Enums\AttendanceStatus;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'status',

        'time_in',
        'time_out',

        'worked_minutes',
        'late_minutes',
        'undertime_minutes',
        'overtime_minutes',

        'salary_contract_id',

        'remarks',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'status' => AttendanceStatus::class,
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}