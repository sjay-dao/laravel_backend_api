<?php

namespace App\Domains\Employee\Models;

use App\Domains\System\Models\Branch;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Domains\Employee\Models\SalaryContract;
use App\Domains\Employee\Models\EmployeeScheduleAssignment;

use App\Domains\Reference\Models\Reference;
class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = ['employee_no', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date', 'gender', 'email', 'mobile_number', 'phone_number', 'address', 'branch_id', 'department_id', 'position_id', 'employment_status_id', 'hire_date', 'regularization_date', 'separation_date', 'tin', 'sss_number', 'philhealth_number', 'pagibig_number', 'bank_name', 'bank_account_name', 'bank_account_number', 'notes', 'created_by', 'updated_by'];
    protected $casts = ['birth_date' => 'date', 'hire_date' => 'date', 'regularization_date' => 'date', 'separation_date' => 'date'];
    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string { return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix]))); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function position() { return $this->belongsTo(Position::class); }
    public function emergencyContacts() { return $this->hasMany(EmergencyContact::class); }
    public function salarySetups() { return $this->hasMany(SalarySetup::class); }
    public function attachments() { return $this->hasMany(Attachment::class); }
    public function transactions() { return $this->hasMany(EmployeeTransaction::class); }
    public function activities() { return $this->hasMany(ActivityLog::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function salaryContracts()
    {
        return $this->hasMany(SalaryContract::class);
    }
    public function scheduleAssignments()
    {
        return $this->hasMany(EmployeeScheduleAssignment::class);
    }

    public function employmentStatus()
    {
        return $this->belongsTo(
            Reference::class,
            'employment_status_id'
        );
    }
}
