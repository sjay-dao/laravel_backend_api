<?php

namespace App\Domains\Employee\Models;

use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalarySetup extends Model
{
    use SoftDeletes;
    protected $table = 'employee_salary_setups';
    protected $fillable = ['employee_id', 'basic_rate', 'rate_type', 'pay_frequency', 'currency', 'allowance_amount', 'effective_from', 'effective_to', 'remarks', 'created_by'];
    protected $casts = ['basic_rate' => 'decimal:2', 'allowance_amount' => 'decimal:2', 'effective_from' => 'date', 'effective_to' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
